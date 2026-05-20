<?php

namespace App\Http\Controllers;

use App\Models\ChatReview;
use App\Models\KnowledgeUpdateQueue;
use App\Models\LeadQueueActionHistory;
use App\Models\LeadOperationalSnapshot;
use App\Models\Prospect;
use App\Models\ProspectLog;
use App\Models\User;
use App\Services\LeadOperationalSnapshotService;
use App\Services\ManagerInsightService;
use App\Services\ObjectionAnalyticsService;
use App\Services\QueueActionLifecycleService;
use App\Services\SalesDisciplineMetricsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReactApiController extends Controller
{
    public function prospectForm(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->canCreateProspect(), 403);

        return response()->json([
            'sources' => collect(Prospect::SOURCES)->map(fn (string $source) => [
                'value' => $source,
                'label' => strtoupper($source),
            ])->values(),
            'statuses' => collect(Prospect::STATUSES)->map(fn (string $status) => [
                'value' => $status,
                'label' => Prospect::STATUS_LABELS[$status] ?? strtoupper($status),
            ])->values(),
            'types' => collect(ProspectLog::TYPES)->map(fn (string $type) => [
                'value' => $type,
                'label' => strtoupper($type),
            ])->values(),
            'objectionTypes' => $this->objectionTypeOptions(),
            'emotionalStates' => $this->emotionalStateOptions(),
            'canAssignOwner' => !$user->isPenjualan(),
            ...$this->filterOptions($user),
        ]);
    }

    public function prospectDetail(Request $request, Prospect $prospect): JsonResponse
    {
        $this->authorize('viewAny', Prospect::class);
        $user = $request->user();

        abort_unless($this->scopedProspects($user)->whereKey($prospect->id)->exists(), 404);

        $prospect->load([
            'owner:id,name',
            'logs' => fn ($q) => $q->with('user:id,name')->latest('log_date'),
        ]);

        return response()->json([
            'prospect' => [
                'id' => $prospect->id,
                'prospectCode' => $prospect->prospect_code,
                'name' => $prospect->name,
                'company' => $prospect->company ?: 'Belum ada nama perusahaan',
                'phone' => $prospect->phone,
                'email' => $prospect->email,
                'source' => $prospect->source,
                'owner' => $prospect->owner?->name ?? '-',
                'ownerId' => $prospect->owner_id ? (string) $prospect->owner_id : '',
                'accountCategory' => $prospect->account_category,
                'accountCategoryLabel' => Prospect::ACCOUNT_CATEGORY_LABELS[$prospect->account_category] ?? strtoupper($prospect->account_category),
                'status' => $prospect->status,
                'statusLabel' => Prospect::STATUS_LABELS[$prospect->status] ?? strtoupper($prospect->status),
                'gptMode' => $prospect->gpt_mode,
                'userTemperature' => $prospect->user_temperature,
                'dominantEmotion' => $prospect->dominant_emotion,
                'bridgeCandidate' => (bool) $prospect->bridge_candidate,
                'bridgeStatus' => $prospect->bridge_status,
                'lostReason' => $prospect->lost_reason,
                'mainObjection' => $prospect->main_objection,
                'priority' => (int) $prospect->priority,
                'nextFollowUpDate' => $prospect->next_follow_up_date?->format('Y-m-d'),
                'nextFollowUpDateLabel' => $prospect->next_follow_up_date?->format('d M Y') ?: '-',
                'estimationValue' => (float) ($prospect->estimation_value ?? 0),
                'estimationValueLabel' => 'Rp ' . number_format((float) ($prospect->estimation_value ?? 0), 0, ',', '.'),
                'notes' => $prospect->notes,
                ...$this->prospectOperationalFields($prospect),
            ],
            'logs' => $prospect->logs->map(fn (ProspectLog $log) => [
                'id' => $log->id,
                'dateLabel' => $log->log_date?->format('d M Y') ?: '-',
                'activityType' => $log->activity_type,
                'activityTypeLabel' => strtoupper($log->activity_type),
                'summary' => $log->summary,
                'result' => $log->result ?: '-',
                'objectionType' => $log->objection_type,
                'objectionTypeLabel' => $log->objection_type ? (ProspectLog::OBJECTION_TYPE_LABELS[$log->objection_type] ?? strtoupper($log->objection_type)) : null,
                'objectionDetail' => $log->objection_detail,
                'emotionalState' => $log->emotional_state,
                'emotionalStateLabel' => $log->emotional_state ? (ProspectLog::EMOTIONAL_STATE_LABELS[$log->emotional_state] ?? strtoupper($log->emotional_state)) : null,
                'user' => $log->user?->name ?? '-',
            ])->values(),
            'canEdit' => $user->canEditProspect($prospect),
            'editUrl' => route('prospects.edit', $prospect),
            'updateUrl' => route('prospects.update', $prospect),
            'storeLogUrl' => route('react-api.prospects.logs.store', $prospect),
            'types' => collect(ProspectLog::TYPES)->map(fn (string $type) => [
                'value' => $type,
                'label' => strtoupper($type),
            ])->values(),
            'objectionTypes' => $this->objectionTypeOptions(),
            'emotionalStates' => $this->emotionalStateOptions(),
        ]);
    }

    public function storeProspectLog(
        Request $request,
        Prospect $prospect,
        LeadOperationalSnapshotService $snapshotService
    ): JsonResponse
    {
        $this->authorize('viewAny', Prospect::class);
        $user = $request->user();

        abort_unless($this->scopedProspects($user)->whereKey($prospect->id)->exists(), 404);
        abort_unless($user->canEditProspect($prospect), 403);

        $validated = $request->validate([
            'daily_activity_type' => ['required', Rule::in(ProspectLog::TYPES)],
            'daily_summary' => ['required', 'string', 'max:255'],
            'daily_result' => ['nullable', 'string', 'max:2000'],
            'objection_type' => ['nullable', Rule::in(ProspectLog::OBJECTION_TYPES)],
            'objection_detail' => ['nullable', 'string', 'max:2000'],
            'emotional_state' => ['nullable', Rule::in(ProspectLog::EMOTIONAL_STATES)],
        ]);

        ProspectLog::query()->create([
            'prospect_id' => $prospect->id,
            'user_id' => $user->id,
            'log_date' => now()->toDateString(),
            'activity_type' => $validated['daily_activity_type'],
            'summary' => $validated['daily_summary'],
            'result' => $validated['daily_result'] ?? null,
            'objection_type' => $validated['objection_type'] ?? null,
            'objection_detail' => $validated['objection_detail'] ?? null,
            'emotional_state' => $validated['emotional_state'] ?? null,
        ]);

        $snapshotService->recomputeLead($prospect->fresh());

        return response()->json(['message' => 'Input harian tersimpan.']);
    }

    public function prospectTimeline(Request $request, Prospect $prospect): JsonResponse
    {
        $this->authorize('viewAny', Prospect::class);
        $user = $request->user();

        abort_unless($this->scopedProspects($user)->whereKey($prospect->id)->exists(), 404);

        $limit = max(1, min(100, (int) $request->integer('limit', 30)));

        $events = $prospect->timelineEvents()
            ->latest('event_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'items' => $events->map(fn ($event) => [
                'id' => $event->id,
                'eventType' => $event->event_type,
                'eventAt' => $event->event_at?->toISOString(),
                'eventAtLabel' => $event->event_at?->format('d/m/Y H:i:s'),
                'actorType' => $event->actor_type,
                'actorId' => $event->actor_id,
                'source' => $event->source,
                'payload' => $event->payload,
                'refType' => $event->ref_type,
                'refId' => $event->ref_id,
            ])->values(),
        ]);
    }

    public function chatReviewForm(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->canCreateChatReview(), 403);

        $prospects = $this->scopedProspects($user)
            ->orderByDesc('updated_at')
            ->limit(200)
            ->get(['id', 'prospect_code', 'name']);

        return response()->json([
            'channels' => collect(ChatReview::CHANNELS)->map(fn (string $channel) => [
                'value' => $channel,
                'label' => strtoupper($channel),
            ])->values(),
            'outcomes' => collect(ChatReview::OUTCOMES)->map(fn (string $outcome) => [
                'value' => $outcome,
                'label' => strtoupper($outcome),
            ])->values(),
            'statuses' => collect(ChatReview::STATUSES)->map(fn (string $status) => [
                'value' => $status,
                'label' => strtoupper($status),
            ])->values(),
            'objectionTypes' => $this->objectionTypeOptions(),
            'emotionalStates' => $this->emotionalStateOptions(),
            'prospects' => $prospects->map(fn (Prospect $prospect) => [
                'value' => (string) $prospect->id,
                'label' => $prospect->prospect_code . ' - ' . $prospect->name,
            ])->values(),
        ]);
    }

    public function chatReviewDetail(Request $request, ChatReview $chatReview): JsonResponse
    {
        abort_unless($request->user()->can('access-chat-reviews'), 403);
        $user = $request->user();

        abort_unless($this->scopedChatReviews($user)->whereKey($chatReview->id)->exists(), 404);

        $chatReview->load([
            'submitter:id,name,role',
            'prospect:id,prospect_code,name,account_category',
        ]);

        return response()->json([
            'review' => [
                'id' => $chatReview->id,
                'title' => $chatReview->title,
                'channel' => $chatReview->channel,
                'outcome' => $chatReview->outcome,
                'objectionType' => $chatReview->objection_type,
                'objectionDetail' => $chatReview->objection_detail,
                'emotionalState' => $chatReview->emotional_state,
                'status' => $chatReview->status,
                'customerName' => $chatReview->customer_name,
                'customerCompany' => $chatReview->customer_company,
                'prospectId' => $chatReview->prospect_id ? (string) $chatReview->prospect_id : '',
                'chatSummary' => $chatReview->chat_summary,
                'chatExcerpt' => $chatReview->chat_excerpt,
                'whatWorked' => $chatReview->what_worked,
                'whatFailed' => $chatReview->what_failed,
                'suggestedKnowledgeUpdate' => $chatReview->suggested_knowledge_update,
                'submitter' => $chatReview->submitter?->name ?? '-',
                'submitterRole' => $chatReview->submitter?->roleLabel() ?? '-',
                'prospectCode' => $chatReview->prospect?->prospect_code ?? '-',
                'prospectName' => $chatReview->prospect?->name ?? '-',
                'accountCategoryLabel' => Prospect::ACCOUNT_CATEGORY_LABELS[$chatReview->prospect?->account_category] ?? '-',
            ],
            'canEdit' => $user->canEditChatReview($chatReview),
            'editUrl' => route('chat-reviews.edit', $chatReview),
            'updateUrl' => route('chat-reviews.update', $chatReview),
        ]);
    }

    public function dashboard(Request $request, SalesDisciplineMetricsService $disciplineMetrics): JsonResponse
    {
        $this->authorize('viewAny', Prospect::class);
        $user = $request->user();
        $prospects = $this->applyProspectFilters(Prospect::query(), $request);

        $this->scopeProspects($prospects, $user);

        $totalProspects = (clone $prospects)->count();
        $miniProspects = (clone $prospects)->where('account_category', 'mini')->count();
        $regularProspects = (clone $prospects)->where('account_category', 'reguler')->count();
        $openProspects = (clone $prospects)
            ->whereIn('status', [
                Prospect::STATUS_BARU,
                Prospect::STATUS_DIHUBUNGI,
                Prospect::STATUS_DIBALAS,
                Prospect::STATUS_SEDANG_BERJALAN,
                Prospect::STATUS_TINDAK_LANJUT,
                Prospect::STATUS_PENUTUPAN,
            ])
            ->count();
        $wonProspects = (clone $prospects)->where('status', Prospect::STATUS_PENUTUPAN)->count();
        $miniWonProspects = (clone $prospects)->where('account_category', 'mini')->where('status', Prospect::STATUS_PENUTUPAN)->count();
        $regularWonProspects = (clone $prospects)->where('account_category', 'reguler')->where('status', Prospect::STATUS_PENUTUPAN)->count();
        $bridgeCandidatesCount = (clone $prospects)->where('bridge_candidate', true)->count();
        $bridgeMovedCount = (clone $prospects)->where('bridge_status', 'moved')->count();
        $overdueCount = $this->overdueFollowUpQuery(clone $prospects)->count();
        $dueTodayCount = $this->dueTodayFollowUpQuery(clone $prospects)->count();
        $staleCount = $this->staleProspectsQuery(clone $prospects)->count();
        $staleOverdueCount = $this->staleProspectsQuery($this->overdueFollowUpQuery(clone $prospects))->count();
        $highPriorityCount = $this->highPriorityProspectsQuery(clone $prospects)->count();
        $agingOverSevenDaysCount = $this->agingOverSevenDaysQuery(clone $prospects)->count();
        $activeLeadCount = $this->activeFollowUpQuery(clone $prospects)->count();
        $visibleSalesUsers = $this->visibleSalesUsers($user);
        $salesDisciplineMetrics = collect($disciplineMetrics->forSales($visibleSalesUsers, clone $prospects));
        $crmHealthScore = $salesDisciplineMetrics->count() > 0
            ? round($salesDisciplineMetrics->avg('crm_activity_score'), 1)
            : 100;
        $activeSalesTodayCount = $salesDisciplineMetrics
            ->filter(fn (array $metric) => $metric['daily_activity_count'] > 0)
            ->count();

        $todayInputQuery = ProspectLog::query()->whereDate('log_date', now()->toDateString());
        if ($user->isPenjualan()) {
            $todayInputQuery->where('user_id', $user->id);
        } elseif ($user->isManager()) {
            $todayInputQuery->whereHas('user', fn (Builder $q) => $q->where('team_id', $user->team_id));
        } elseif ($user->isKepala()) {
            $todayInputQuery->whereHas('user', fn (Builder $q) => $q->where('unit_id', $user->unit_id));
        }

        $statusSummary = (clone $prospects)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');
        $lostReasonSummary = (clone $prospects)
            ->whereNotNull('lost_reason')
            ->select('lost_reason', DB::raw('COUNT(*) as total'))
            ->groupBy('lost_reason')
            ->orderByDesc('total')
            ->limit(8)
            ->pluck('total', 'lost_reason');

        $upcomingFollowUp = (clone $prospects)
            ->with('owner:id,name')
            ->whereNotNull('next_follow_up_date')
            ->orderBy('next_follow_up_date')
            ->limit(8)
            ->get();

        return response()->json([
            'kpis' => [
                'totalProspects' => $totalProspects,
                'miniProspects' => $miniProspects,
                'regularProspects' => $regularProspects,
                'openProspects' => $openProspects,
                'wonProspects' => $wonProspects,
                'bridgeCandidatesCount' => $bridgeCandidatesCount,
                'bridgeMovedCount' => $bridgeMovedCount,
                'miniConversionPercent' => $miniProspects > 0 ? round(($miniWonProspects / $miniProspects) * 100, 2) : 0,
                'regularConversionPercent' => $regularProspects > 0 ? round(($regularWonProspects / $regularProspects) * 100, 2) : 0,
                'todayInputCount' => $todayInputQuery->count(),
                'overdueCount' => $overdueCount,
                'dueTodayCount' => $dueTodayCount,
                'staleCount' => $staleCount,
                'staleOverdueCount' => $staleOverdueCount,
                'highPriorityCount' => $highPriorityCount,
                'agingOverSevenDaysCount' => $agingOverSevenDaysCount,
                'crmHealthScore' => $crmHealthScore,
                'overdueRatio' => $activeLeadCount > 0 ? round(($overdueCount / $activeLeadCount) * 100, 1) : 0,
                'activeSalesTodayCount' => $activeSalesTodayCount,
                'healthPercent' => $totalProspects > 0 ? round(($wonProspects / $totalProspects) * 100, 2) : 0,
            ],
            'statusSummary' => collect(Prospect::STATUSES)->map(fn (string $status) => [
                'key' => $status,
                'label' => Prospect::STATUS_LABELS[$status] ?? ucfirst($status),
                'total' => (int) ($statusSummary[$status] ?? 0),
            ])->values(),
            'upcomingFollowUp' => $upcomingFollowUp->map(fn (Prospect $prospect) => [
                'id' => $prospect->id,
                'prospectCode' => $prospect->prospect_code,
                'name' => $prospect->name,
                'owner' => $prospect->owner?->name ?? '-',
                'nextFollowUpDate' => $prospect->next_follow_up_date?->format('Y-m-d'),
                'nextFollowUpDateLabel' => $prospect->next_follow_up_date?->format('d M Y') ?: '-',
                'status' => $prospect->status,
                'statusLabel' => Prospect::STATUS_LABELS[$prospect->status] ?? $prospect->status,
                ...$this->prospectOperationalFields($prospect),
                'detailUrl' => route('prospects.show', $prospect),
            ])->values(),
            'lostReasonSummary' => collect($lostReasonSummary)->map(fn ($total, $reason) => [
                'key' => $reason,
                'label' => Prospect::LOST_REASON_LABELS[$reason] ?? strtoupper($reason),
                'total' => (int) $total,
            ])->values(),
            'disciplineSnapshot' => [
                'topOverdueSales' => $salesDisciplineMetrics
                    ->sortByDesc('overdue_lead_count')
                    ->take(3)
                    ->values(),
                'mostDisciplinedSales' => $salesDisciplineMetrics
                    ->sortByDesc('crm_activity_score')
                    ->take(3)
                    ->values(),
                'salesWithoutActivityToday' => $salesDisciplineMetrics
                    ->filter(fn (array $metric) => $metric['daily_activity_count'] === 0 && $metric['active_lead_count'] > 0)
                    ->values(),
            ],
            'filters' => [
                'current' => $this->currentFilters($request),
                ...$this->filterOptions($user),
            ],
        ]);
    }

    public function actionCenter(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Prospect::class);
        $user = $request->user();
        $prospects = $this->applyProspectFilters(Prospect::query(), $request);
        $this->scopeProspects($prospects, $user);

        $overdueLeads = $this->overdueFollowUpQuery(clone $prospects)
            ->with('owner:id,name')
            ->orderBy('next_follow_up_date')
            ->limit(10)
            ->get();

        $warmUncontacted = (clone $prospects)
            ->with('owner:id,name')
            ->where('user_temperature', 'warm')
            ->whereDoesntHave('whatsAppMessages')
            ->whereDoesntHave('logs')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $ghostRisk = (clone $prospects)
            ->with('owner:id,name')
            ->whereHas('whatsAppMessages', fn (Builder $q) => $q
                ->where('direction', 'outbound')
                ->where('sent_at', '>=', now()->subHours(48)))
            ->whereDoesntHave('whatsAppMessages', fn (Builder $q) => $q
                ->where('direction', 'inbound')
                ->where('sent_at', '>=', now()->subHours(48)))
            ->withCount([
                'whatsAppMessages as outbound_last_48h_count' => fn (Builder $q) => $q
                    ->where('direction', 'outbound')
                    ->where('sent_at', '>=', now()->subHours(48)),
                'whatsAppMessages as inbound_last_48h_count' => fn (Builder $q) => $q
                    ->where('direction', 'inbound')
                    ->where('sent_at', '>=', now()->subHours(48)),
            ])
            ->orderByDesc('outbound_last_48h_count')
            ->limit(10)
            ->get();

        $hotOpportunities = (clone $prospects)
            ->with('owner:id,name')
            ->whereIn('status', [Prospect::STATUS_TINDAK_LANJUT, Prospect::STATUS_PENUTUPAN])
            ->where('user_temperature', 'hot')
            ->where(function (Builder $q) {
                $q->where('last_activity_at', '<', now()->subHours(12))
                    ->orWhereNull('last_activity_at');
            })
            ->orderBy('next_follow_up_date')
            ->limit(10)
            ->get();

        return response()->json([
            'summary' => [
                'overdueCount' => $overdueLeads->count(),
                'warmUncontactedCount' => $warmUncontacted->count(),
                'ghostRiskCount' => $ghostRisk->count(),
                'hotOpportunityCount' => $hotOpportunities->count(),
            ],
            'queues' => [
                'overdue' => $overdueLeads->map(fn (Prospect $lead) => $this->actionLeadItem($lead))->values(),
                'warmUncontacted' => $warmUncontacted->map(fn (Prospect $lead) => $this->actionLeadItem($lead))->values(),
                'ghostRisk' => $ghostRisk->map(fn (Prospect $lead) => $this->actionLeadItem($lead, [
                    'outbound_last_48h_count' => (int) ($lead->outbound_last_48h_count ?? 0),
                    'inbound_last_48h_count' => (int) ($lead->inbound_last_48h_count ?? 0),
                ]))->values(),
                'hotOpportunity' => $hotOpportunities->map(fn (Prospect $lead) => $this->actionLeadItem($lead))->values(),
            ],
        ]);
    }

    public function queue(Request $request, LeadOperationalSnapshotService $snapshotService): JsonResponse
    {
        $this->authorize('viewAny', Prospect::class);
        $user = $request->user();

        $baseProspects = $this->applyProspectFilters(Prospect::query(), $request);
        $this->scopeProspects($baseProspects, $user);

        $leadIds = (clone $baseProspects)->pluck('prospects.id');
        $staleCutoff = now()->subMinutes(15);

        $staleLeadIds = LeadOperationalSnapshot::query()
            ->whereIn('lead_id', $leadIds)
            ->where(function (Builder $q) use ($staleCutoff) {
                $q->whereNull('computed_at')->orWhere('computed_at', '<', $staleCutoff);
            })
            ->pluck('lead_id');

        $missingLeadIds = $leadIds->diff(
            LeadOperationalSnapshot::query()
                ->whereIn('lead_id', $leadIds)
                ->pluck('lead_id')
        );

        $recomputeIds = $staleLeadIds->merge($missingLeadIds)->unique()->values();
        if ($recomputeIds->isNotEmpty()) {
            $recomputeLeads = Prospect::query()->whereIn('id', $recomputeIds)->get();
            $snapshotService->recomputeCollection($recomputeLeads);
        }

        $sortBy = $request->string('sort')->toString() ?: 'priority';
        $perPage = max(10, min(100, (int) $request->integer('per_page', 25)));
        $page = max(1, (int) $request->integer('page', 1));
        $offset = ($page - 1) * $perPage;

        $queue = LeadOperationalSnapshot::query()
            ->with(['lead.owner:id,name', 'queueState'])
            ->whereIn('lead_id', $leadIds)
            ->when($request->string('priority_band')->toString(), fn (Builder $q, string $band) => $q->where('priority_band', $band))
            ->when($request->boolean('ghost_risk'), fn (Builder $q) => $q->where('ghost_risk_score', '>=', 70))
            ->when($request->boolean('overdue_only'), fn (Builder $q) => $q->where('overdue_minutes', '>', 0));

        if ($sortBy === 'sla') {
            $queue->orderByDesc('overdue_minutes')->orderByDesc('priority_score');
        } elseif ($sortBy === 'fresh') {
            $queue->orderBy('next_action_expires_at')->orderByDesc('priority_score');
        } else {
            $queue->orderByDesc('priority_score')->orderByDesc('overdue_minutes');
        }

        $items = $queue->get()->filter(function (LeadOperationalSnapshot $snapshot): bool {
            return $this->isSnapshotVisibleInQueue($snapshot);
        })->values();

        $total = $items->count();
        $items = $items->slice($offset, $perPage)->values();

        return response()->json([
            'items' => $items->map(function (LeadOperationalSnapshot $snapshot) {
                $lead = $snapshot->lead;
                if (! $lead) {
                    return null;
                }

                return [
                    'leadId' => $lead->id,
                    'prospectCode' => $lead->prospect_code,
                    'name' => $lead->name,
                    'owner' => $lead->owner?->name ?? '-',
                    'status' => $lead->status,
                    'statusLabel' => Prospect::STATUS_LABELS[$lead->status] ?? strtoupper($lead->status),
                    'priorityScore' => (int) $snapshot->priority_score,
                    'priorityBand' => $snapshot->priority_band,
                    'ghostRiskScore' => (int) $snapshot->ghost_risk_score,
                    'overdueMinutes' => (int) $snapshot->overdue_minutes,
                    'responseDelayMinutes' => (int) $snapshot->response_delay_minutes,
                    'nextActionCode' => $snapshot->next_action_code,
                    'nextActionLabel' => $this->nextActionLabel($snapshot->next_action_code),
                    'nextActionConfidence' => $snapshot->next_action_confidence !== null ? (float) $snapshot->next_action_confidence : null,
                    'nextActionExpiresAtLabel' => $snapshot->next_action_expires_at?->format('d M H:i'),
                    'computedAtLabel' => $snapshot->computed_at?->format('d M H:i:s'),
                    'lifecycleState' => $snapshot->queueState?->state ?? 'active',
                    'detailUrl' => route('prospects.show', $lead),
                ];
            })->filter()->values(),
            'meta' => [
                'currentPage' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'lastPage' => max(1, (int) ceil($total / $perPage)),
            ],
            'filters' => [
                'current' => [
                    'sort' => $sortBy,
                    'priority_band' => $request->string('priority_band')->toString(),
                    'ghost_risk' => $request->string('ghost_risk')->toString(),
                    'overdue_only' => $request->string('overdue_only')->toString(),
                ],
                'reasonTags' => [
                    ['value' => 'contacted_via_other_channel', 'label' => 'Contacted via other channel'],
                    ['value' => 'waiting_customer_reply', 'label' => 'Waiting customer reply'],
                    ['value' => 'duplicate_queue_item', 'label' => 'Duplicate queue item'],
                    ['value' => 'not_relevant_now', 'label' => 'Not relevant now'],
                    ['value' => 'other', 'label' => 'Other'],
                ],
            ],
        ]);
    }

    public function queueMarkDone(
        Request $request,
        Prospect $prospect,
        QueueActionLifecycleService $lifecycle
    ): JsonResponse {
        $this->authorize('viewAny', Prospect::class);
        $user = $request->user();
        abort_unless($this->scopedProspects($user)->whereKey($prospect->id)->exists(), 404);

        $validated = $request->validate([
            'reason_tag' => ['required', 'string', 'max:64'],
            'reason_note' => ['nullable', 'string', 'max:255'],
        ]);

        $state = $lifecycle->markDone($prospect, $user, $validated['reason_tag'], $validated['reason_note'] ?? null);

        return response()->json([
            'success' => true,
            'state' => $state->state,
        ]);
    }

    public function queueSnooze(
        Request $request,
        Prospect $prospect,
        QueueActionLifecycleService $lifecycle
    ): JsonResponse {
        $this->authorize('viewAny', Prospect::class);
        $user = $request->user();
        abort_unless($this->scopedProspects($user)->whereKey($prospect->id)->exists(), 404);

        $validated = $request->validate([
            'duration' => ['required', 'in:30m,2h,tomorrow'],
            'reason_tag' => ['required', 'string', 'max:64'],
            'reason_note' => ['nullable', 'string', 'max:255'],
        ]);

        $state = $lifecycle->snooze(
            $prospect,
            $user,
            $validated['duration'],
            $validated['reason_tag'],
            $validated['reason_note'] ?? null
        );

        return response()->json([
            'success' => true,
            'state' => $state->state,
            'snoozedUntilLabel' => $state->snoozed_until?->format('d M H:i'),
        ]);
    }

    public function queueDismiss(
        Request $request,
        Prospect $prospect,
        QueueActionLifecycleService $lifecycle
    ): JsonResponse {
        $this->authorize('viewAny', Prospect::class);
        $user = $request->user();
        abort_unless($this->scopedProspects($user)->whereKey($prospect->id)->exists(), 404);

        $validated = $request->validate([
            'reason_tag' => ['required', 'string', 'max:64'],
            'reason_note' => ['nullable', 'string', 'max:255'],
        ]);

        $state = $lifecycle->dismiss($prospect, $user, $validated['reason_tag'], $validated['reason_note'] ?? null);

        return response()->json([
            'success' => true,
            'state' => $state->state,
            'dismissedUntilLabel' => $state->dismissed_until?->format('d M H:i'),
        ]);
    }

    public function queueHistory(Request $request, Prospect $prospect): JsonResponse
    {
        $this->authorize('viewAny', Prospect::class);
        $user = $request->user();
        abort_unless($this->scopedProspects($user)->whereKey($prospect->id)->exists(), 404);

        $items = LeadQueueActionHistory::query()
            ->where('lead_id', $prospect->id)
            ->with('lead:id,prospect_code,name')
            ->orderByDesc('acted_at')
            ->limit(30)
            ->get();

        return response()->json([
            'items' => $items->map(fn (LeadQueueActionHistory $item) => [
                'id' => $item->id,
                'actionType' => $item->action_type,
                'reasonTag' => $item->reason_tag,
                'reasonNote' => $item->reason_note,
                'actedAtLabel' => $item->acted_at?->format('d M Y H:i:s'),
                'actedByUserId' => $item->acted_by_user_id,
            ])->values(),
        ]);
    }

    public function prospects(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Prospect::class);
        $user = $request->user();
        $prospects = $this->applyProspectFilters(
            $this->scopedProspects($user)->with(['owner:id,name', 'team:id,name', 'unit:id,name']),
            $request
        )->latest()->paginate(12)->withQueryString();

        return response()->json([
            'items' => collect($prospects->items())->map(fn (Prospect $prospect) => [
                'id' => $prospect->id,
                'prospectCode' => $prospect->prospect_code,
                'name' => $prospect->name,
                'company' => $prospect->company ?: '-',
                'accountCategory' => $prospect->account_category,
                'accountCategoryLabel' => Prospect::ACCOUNT_CATEGORY_LABELS[$prospect->account_category] ?? strtoupper($prospect->account_category),
                'gptMode' => $prospect->gpt_mode,
                'gptModeLabel' => Prospect::GPT_MODE_LABELS[$prospect->gpt_mode] ?? '-',
                'userTemperature' => $prospect->user_temperature,
                'userTemperatureLabel' => Prospect::USER_TEMPERATURE_LABELS[$prospect->user_temperature] ?? '-',
                'dominantEmotion' => $prospect->dominant_emotion,
                'dominantEmotionLabel' => Prospect::DOMINANT_EMOTION_LABELS[$prospect->dominant_emotion] ?? '-',
                'mainObjection' => $prospect->main_objection,
                'bridgeCandidate' => (bool) $prospect->bridge_candidate,
                'bridgeStatus' => $prospect->bridge_status,
                'bridgeStatusLabel' => Prospect::BRIDGE_STATUS_LABELS[$prospect->bridge_status] ?? '-',
                'lostReason' => $prospect->lost_reason,
                'lostReasonLabel' => Prospect::LOST_REASON_LABELS[$prospect->lost_reason] ?? '-',
                'owner' => $prospect->owner?->name ?? '-',
                'team' => $prospect->team?->name ?? '-',
                'unit' => $prospect->unit?->name ?? '-',
                'status' => $prospect->status,
                'statusLabel' => Prospect::STATUS_LABELS[$prospect->status] ?? strtoupper($prospect->status),
                'nextFollowUpDate' => $prospect->next_follow_up_date?->format('Y-m-d'),
                'nextFollowUpDateLabel' => $prospect->next_follow_up_date?->format('d M Y') ?: '-',
                'isOverdue' => $prospect->follow_up_state === Prospect::FOLLOW_UP_STATE_OVERDUE,
                ...$this->prospectOperationalFields($prospect),
                'showUrl' => route('prospects.show', $prospect),
                'editUrl' => route('prospects.edit', $prospect),
                'deleteUrl' => route('prospects.destroy', $prospect),
                'canEdit' => $user->canEditProspect($prospect),
                'canDelete' => $user->canDeleteProspect($prospect),
            ])->values(),
            'meta' => [
                'currentPage' => $prospects->currentPage(),
                'lastPage' => $prospects->lastPage(),
                'perPage' => $prospects->perPage(),
                'total' => $prospects->total(),
            ],
            'filters' => [
                'current' => $this->currentFilters($request),
                'statuses' => collect(Prospect::STATUSES)->map(fn (string $status) => [
                    'value' => $status,
                    'label' => Prospect::STATUS_LABELS[$status] ?? strtoupper($status),
                ])->values(),
                ...$this->filterOptions($user),
            ],
            'permissions' => [
                'canCreateProspect' => $user->canCreateProspect(),
                'createUrl' => route('prospects.create'),
            ],
        ]);
    }

    public function pipeline(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Prospect::class);
        $user = $request->user();
        $prospects = $this->applyProspectFilters(
            $this->scopedProspects($user)->with(['owner:id,name']),
            $request
        )
            ->orderBy('priority')
            ->orderBy('next_follow_up_date')
            ->orderByDesc('updated_at')
            ->get();

        $today = now()->startOfDay();
        $dueSoonLimit = now()->addDays(3)->endOfDay();

        return response()->json([
            'columns' => collect(Prospect::STATUSES)->map(function (string $status) use ($prospects, $user) {
                $items = $prospects->where('status', $status)->values();

                return [
                    'status' => $status,
                    'label' => Prospect::STATUS_LABELS[$status] ?? strtoupper($status),
                    'count' => $items->count(),
                    'items' => $items->map(fn (Prospect $prospect) => [
                        'id' => $prospect->id,
                        'prospectCode' => $prospect->prospect_code,
                        'name' => $prospect->name,
                        'company' => $prospect->company ?: '-',
                        'owner' => $prospect->owner?->name ?? '-',
                        'accountCategoryLabel' => Prospect::ACCOUNT_CATEGORY_LABELS[$prospect->account_category] ?? strtoupper($prospect->account_category),
                        'gptMode' => $prospect->gpt_mode,
                        'gptModeLabel' => Prospect::GPT_MODE_LABELS[$prospect->gpt_mode] ?? '-',
                        'userTemperature' => $prospect->user_temperature,
                        'userTemperatureLabel' => Prospect::USER_TEMPERATURE_LABELS[$prospect->user_temperature] ?? '-',
                        'dominantEmotion' => $prospect->dominant_emotion,
                        'dominantEmotionLabel' => Prospect::DOMINANT_EMOTION_LABELS[$prospect->dominant_emotion] ?? '-',
                        'mainObjection' => $prospect->main_objection,
                        'bridgeCandidate' => (bool) $prospect->bridge_candidate,
                        'bridgeStatus' => $prospect->bridge_status,
                        'bridgeStatusLabel' => Prospect::BRIDGE_STATUS_LABELS[$prospect->bridge_status] ?? '-',
                        'status' => $prospect->status,
                        'statusLabel' => Prospect::STATUS_LABELS[$prospect->status] ?? strtoupper($prospect->status),
                        'nextFollowUpDate' => $prospect->next_follow_up_date?->format('Y-m-d'),
                        'nextFollowUpDateLabel' => $prospect->next_follow_up_date?->format('d M Y') ?: '-',
                        'isOverdue' => $prospect->follow_up_state === Prospect::FOLLOW_UP_STATE_OVERDUE,
                        ...$this->prospectOperationalFields($prospect),
                        'quickUpdateUrl' => route('prospects.quick-update', $prospect),
                        'detailUrl' => route('prospects.show', $prospect),
                        'canEdit' => $user->canEditProspect($prospect) || ($user->isManager() && $prospect->team_id === $user->team_id) || $user->isSuperAdmin(),
                    ])->values(),
                ];
            })->values(),
            'metrics' => [
                'overdueCount' => $prospects
                    ->filter(fn (Prospect $prospect) => $prospect->follow_up_state === Prospect::FOLLOW_UP_STATE_OVERDUE)
                    ->count(),
                'dueTodayCount' => $prospects
                    ->filter(fn (Prospect $prospect) => $prospect->follow_up_state === Prospect::FOLLOW_UP_STATE_TODAY)
                    ->count(),
                'dueSoonCount' => $prospects
                    ->filter(fn (Prospect $prospect) => $prospect->follow_up_state === Prospect::FOLLOW_UP_STATE_SOON)
                    ->count(),
            ],
            'filters' => [
                'current' => $this->currentFilters($request),
                'statuses' => collect(Prospect::STATUSES)->map(fn (string $status) => [
                    'value' => $status,
                    'label' => Prospect::STATUS_LABELS[$status] ?? strtoupper($status),
                ])->values(),
                ...$this->filterOptions($user),
            ],
        ]);
    }

    public function performance(Request $request, SalesDisciplineMetricsService $disciplineMetrics): JsonResponse
    {
        abort_unless($request->user()->can('access-performance'), 403);
        $user = $request->user();
        $from = $request->date('from')?->startOfDay() ?? now()->startOfMonth();
        $to = $request->date('to')?->endOfDay() ?? now()->endOfDay();
        $baseProspects = $this->applyProspectFilters($this->scopedProspects($user), $request);

        $salesUsers = User::query()
            ->where('role', User::ROLE_PENJUALAN)
            ->when($user->isPenjualan(), fn (Builder $q) => $q->where('id', $user->id))
            ->when($user->isManager(), fn (Builder $q) => $q->where('team_id', $user->team_id))
            ->when($user->isKepala(), fn (Builder $q) => $q->where('unit_id', $user->unit_id))
            ->withCount([
                'prospects as total_prospects' => fn (Builder $q) => $this->applyProspectFilters($q, $request)->whereBetween('created_at', [$from, $to]),
                'prospects as closing_count' => fn (Builder $q) => $this->applyProspectFilters($q, $request)->where('status', Prospect::STATUS_PENUTUPAN)->whereBetween('created_at', [$from, $to]),
                'prospects as mini_closing_count' => fn (Builder $q) => $this->applyProspectFilters($q, $request)->where('account_category', 'mini')->where('status', Prospect::STATUS_PENUTUPAN)->whereBetween('created_at', [$from, $to]),
                'prospects as regular_closing_count' => fn (Builder $q) => $this->applyProspectFilters($q, $request)->where('account_category', 'reguler')->where('status', Prospect::STATUS_PENUTUPAN)->whereBetween('created_at', [$from, $to]),
                'prospects as bridge_conversion_count' => fn (Builder $q) => $this->applyProspectFilters($q, $request)->where('bridge_status', 'moved')->whereBetween('created_at', [$from, $to]),
                'prospects as lost_count' => fn (Builder $q) => $this->applyProspectFilters($q, $request)->where('status', Prospect::STATUS_HILANG)->whereBetween('created_at', [$from, $to]),
                'prospects as overdue_count' => fn (Builder $q) => $q
                    ->where('status', '!=', Prospect::STATUS_HILANG)
                    ->tap(fn (Builder $inner) => $this->applyProspectFilters($inner, $request))
                    ->whereDate('next_follow_up_date', '<', now()->toDateString()),
                'prospectLogs as activity_count' => fn (Builder $q) => $q->whereBetween('log_date', [$from->toDateString(), $to->toDateString()]),
            ])
            ->withSum([
                'prospects as total_value' => fn (Builder $q) => $this->applyProspectFilters($q, $request)->whereBetween('created_at', [$from, $to]),
            ], 'estimation_value')
            ->orderByDesc('closing_count')
            ->orderByDesc('activity_count')
            ->get();

        $disciplineBySales = $disciplineMetrics->forSales($salesUsers, clone $baseProspects);
        $disciplineCollection = collect($disciplineBySales)->values();

        $scopeProspects = clone $baseProspects;
        $statusBreakdown = (clone $scopeProspects)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $objectionFrequency = (clone $scopeProspects)
            ->whereNotNull('main_objection')
            ->where('main_objection', '!=', '')
            ->select('main_objection', DB::raw('COUNT(*) as total'))
            ->groupBy('main_objection')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $overdueProspects = (clone $scopeProspects)
            ->with('owner:id,name')
            ->where('status', '!=', Prospect::STATUS_HILANG)
            ->whereDate('next_follow_up_date', '<', now()->toDateString())
            ->orderBy('next_follow_up_date')
            ->limit(10)
            ->get();

        return response()->json([
            'rows' => $salesUsers->map(function (User $sales) use ($disciplineBySales) {
                $ratio = $sales->total_prospects > 0 ? round(($sales->closing_count / $sales->total_prospects) * 100, 1) : 0;
                $discipline = $disciplineBySales[$sales->id] ?? null;

                return [
                    'id' => $sales->id,
                    'name' => $sales->name,
                    'totalProspects' => $sales->total_prospects,
                    'closingCount' => $sales->closing_count,
                    'miniClosingCount' => $sales->mini_closing_count,
                    'regularClosingCount' => $sales->regular_closing_count,
                    'bridgeConversionCount' => $sales->bridge_conversion_count,
                    'lostCount' => $sales->lost_count,
                    'activityCount' => $sales->activity_count,
                    'overdueCount' => $sales->overdue_count,
                    'totalValue' => (float) ($sales->total_value ?? 0),
                    'totalValueLabel' => 'Rp '.number_format((float) ($sales->total_value ?? 0), 0, ',', '.'),
                    'ratio' => $ratio,
                    'discipline' => $discipline,
                ];
            })->values(),
            'operationalDiscipline' => $disciplineCollection,
            'managerInsights' => [
                'topOverdueSales' => $disciplineCollection
                    ->sortByDesc('overdue_lead_count')
                    ->take(3)
                    ->values(),
                'mostDisciplinedSales' => $disciplineCollection
                    ->sortByDesc('crm_activity_score')
                    ->take(3)
                    ->values(),
                'salesWithoutActivityToday' => $disciplineCollection
                    ->filter(fn (array $metric) => $metric['daily_activity_count'] === 0 && $metric['active_lead_count'] > 0)
                    ->values(),
            ],
            'statusBreakdown' => collect(Prospect::STATUSES)->map(fn (string $status) => [
                'key' => $status,
                'label' => Prospect::STATUS_LABELS[$status] ?? strtoupper($status),
                'total' => (int) ($statusBreakdown[$status] ?? 0),
            ])->values(),
            'overdueProspects' => $overdueProspects->map(fn (Prospect $prospect) => [
                'id' => $prospect->id,
                'prospectCode' => $prospect->prospect_code,
                'name' => $prospect->name,
                'owner' => $prospect->owner?->name ?? '-',
                'status' => $prospect->status,
                'statusLabel' => Prospect::STATUS_LABELS[$prospect->status] ?? strtoupper($prospect->status),
                'nextFollowUpDateLabel' => $prospect->next_follow_up_date?->format('d M Y') ?: '-',
                ...$this->prospectOperationalFields($prospect),
                'detailUrl' => route('prospects.show', $prospect),
            ])->values(),
            'objectionFrequency' => $objectionFrequency->map(fn ($item) => [
                'label' => $item->main_objection,
                'total' => (int) $item->total,
            ])->values(),
            'filters' => [
                'current' => [
                    'from' => $from->format('Y-m-d'),
                    'to' => $to->format('Y-m-d'),
                    ...$this->currentFilters($request),
                ],
                ...$this->filterOptions($user),
            ],
        ]);
    }

    public function objectionInsights(Request $request, ObjectionAnalyticsService $objectionAnalytics): JsonResponse
    {
        abort_unless($request->user()->can('access-performance'), 403);
        $user = $request->user();
        $prospects = $this->applyProspectFilters($this->scopedProspects($user), $request);

        return response()->json($objectionAnalytics->insights($prospects));
    }

    public function managerInsights(Request $request, ManagerInsightService $managerInsights): JsonResponse
    {
        abort_unless($request->user()->can('access-performance'), 403);
        $user = $request->user();
        $prospects = $this->applyProspectFilters($this->scopedProspects($user), $request);

        return response()->json($managerInsights->insights($user, $prospects, $this->visibleSalesUsers($user)));
    }

    public function chatReviews(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ChatReview::class);
        $user = $request->user();

        $reviews = $this->scopedChatReviews($user)
            ->withCount(['managerNotes', 'knowledgeQueues'])
            ->with([
                'submitter:id,name,role',
                'prospect:id,name,prospect_code,account_category',
            ])
            ->when($request->string('outcome')->toString(), fn (Builder $q, string $outcome) => $q->where('outcome', $outcome))
            ->when($request->string('status')->toString(), fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($request->string('account_category')->toString(), function (Builder $q, string $accountCategory) {
                $q->whereHas('prospect', fn (Builder $inner) => $inner->where('account_category', $accountCategory));
            })
            ->when($request->string('q')->toString(), function (Builder $q, string $keyword) {
                $q->where(function (Builder $inner) use ($keyword) {
                    $inner->where('title', 'like', "%{$keyword}%")
                        ->orWhere('customer_name', 'like', "%{$keyword}%")
                        ->orWhere('customer_company', 'like', "%{$keyword}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return response()->json([
            'items' => collect($reviews->items())->map(function (ChatReview $review) use ($user) {
                return [
                    'id' => $review->id,
                    'title' => $review->title,
                    'channel' => $review->channel,
                    'customerName' => $review->customer_name,
                    'customerCompany' => $review->customer_company ?: '-',
                    'outcome' => $review->outcome,
                    'outcomeLabel' => ucfirst($review->outcome),
                    'status' => $review->status,
                    'statusLabel' => str_replace('_', ' ', ucfirst($review->status)),
                    'summary' => $review->chat_summary,
                    'suggestedKnowledgeUpdate' => $review->suggested_knowledge_update,
                    'objectionType' => $review->objection_type,
                    'objectionTypeLabel' => $review->objection_type ? (ProspectLog::OBJECTION_TYPE_LABELS[$review->objection_type] ?? strtoupper($review->objection_type)) : null,
                    'emotionalState' => $review->emotional_state,
                    'emotionalStateLabel' => $review->emotional_state ? (ProspectLog::EMOTIONAL_STATE_LABELS[$review->emotional_state] ?? strtoupper($review->emotional_state)) : null,
                    'submitter' => $review->submitter?->name ?? '-',
                    'submitterRole' => $review->submitter?->roleLabel() ?? '-',
                    'prospectName' => $review->prospect?->name ?? '-',
                    'prospectCode' => $review->prospect?->prospect_code ?? '-',
                    'accountCategory' => $review->prospect?->account_category,
                    'accountCategoryLabel' => Prospect::ACCOUNT_CATEGORY_LABELS[$review->prospect?->account_category] ?? '-',
                    'managerNotesCount' => $review->manager_notes_count,
                    'knowledgeQueueCount' => $review->knowledge_queues_count,
                    'showUrl' => route('chat-reviews.show', $review),
                    'addManagerNoteUrl' => route('chat-reviews.manager-note', $review),
                    'markImportantUrl' => route('knowledge-queue.store', $review),
                    'canComment' => $user->can('comment', $review),
                    'canMarkImportant' => $user->can('markImportant', $review),
                ];
            })->values(),
            'meta' => [
                'currentPage' => $reviews->currentPage(),
                'lastPage' => $reviews->lastPage(),
                'perPage' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
            'filters' => [
                'current' => [
                    'q' => $request->string('q')->toString(),
                    'outcome' => $request->string('outcome')->toString(),
                    'status' => $request->string('status')->toString(),
                    'account_category' => $request->string('account_category')->toString(),
                ],
                'outcomes' => collect(ChatReview::OUTCOMES)->map(fn (string $outcome) => [
                    'value' => $outcome,
                    'label' => ucfirst($outcome),
                ])->values(),
                'statuses' => collect(ChatReview::STATUSES)->map(fn (string $status) => [
                    'value' => $status,
                    'label' => str_replace('_', ' ', ucfirst($status)),
                ])->values(),
                'accountCategories' => collect(Prospect::ACCOUNT_CATEGORIES)->map(fn (string $category) => [
                    'value' => $category,
                    'label' => Prospect::ACCOUNT_CATEGORY_LABELS[$category] ?? strtoupper($category),
                ])->values(),
            ],
            'permissions' => [
                'canCreateChatReview' => $user->can('create', ChatReview::class),
                'createUrl' => route('chat-reviews.create'),
            ],
        ]);
    }

    public function knowledgeQueue(Request $request): JsonResponse
    {
        $this->authorize('viewAny', KnowledgeUpdateQueue::class);
        $user = $request->user();

        $queues = $this->scopedKnowledgeQueues($user)
            ->with([
                'chatReview:id,title,outcome,status,submitted_by,prospect_id',
                'chatReview.submitter:id,name,role',
                'chatReview.prospect:id,name,prospect_code,account_category',
                'requester:id,name',
                'reviewer:id,name',
            ])
            ->when($request->string('status')->toString(), fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($request->string('priority')->toString(), fn (Builder $q, string $priority) => $q->where('priority', $priority))
            ->when($request->string('account_category')->toString(), function (Builder $q, string $accountCategory) {
                $q->whereHas('chatReview.prospect', fn (Builder $inner) => $inner->where('account_category', $accountCategory));
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return response()->json([
            'items' => collect($queues->items())->map(function (KnowledgeUpdateQueue $queue) use ($user) {
                return [
                    'id' => $queue->id,
                    'priority' => $queue->priority,
                    'priorityLabel' => ucfirst($queue->priority),
                    'status' => $queue->status,
                    'statusLabel' => str_replace('_', ' ', ucfirst($queue->status)),
                    'problemPattern' => $queue->problem_pattern,
                    'recommendedUpdate' => $queue->recommended_update,
                    'expectedImpact' => $queue->expected_impact,
                    'superAdminNote' => $queue->super_admin_note,
                    'reviewedAtLabel' => $queue->reviewed_at?->format('d M Y H:i') ?: '-',
                    'chatReviewTitle' => $queue->chatReview?->title ?? '-',
                    'chatReviewOutcome' => $queue->chatReview?->outcome ?? '-',
                    'chatReviewStatus' => $queue->chatReview?->status ?? '-',
                    'prospectName' => $queue->chatReview?->prospect?->name ?? '-',
                    'prospectCode' => $queue->chatReview?->prospect?->prospect_code ?? '-',
                    'accountCategoryLabel' => Prospect::ACCOUNT_CATEGORY_LABELS[$queue->chatReview?->prospect?->account_category] ?? '-',
                    'requester' => $queue->requester?->name ?? '-',
                    'reviewer' => $queue->reviewer?->name ?? '-',
                    'setReviewUrl' => route('knowledge-queue.set-review', $queue),
                    'approveUrl' => route('knowledge-queue.approve', $queue),
                    'rejectUrl' => route('knowledge-queue.reject', $queue),
                    'canReview' => $user->can('approve', $queue),
                ];
            })->values(),
            'meta' => [
                'currentPage' => $queues->currentPage(),
                'lastPage' => $queues->lastPage(),
                'perPage' => $queues->perPage(),
                'total' => $queues->total(),
            ],
            'filters' => [
                'current' => [
                    'status' => $request->string('status')->toString(),
                    'priority' => $request->string('priority')->toString(),
                    'account_category' => $request->string('account_category')->toString(),
                ],
                'statuses' => collect(KnowledgeUpdateQueue::STATUSES)->map(fn (string $status) => [
                    'value' => $status,
                    'label' => str_replace('_', ' ', ucfirst($status)),
                ])->values(),
                'priorities' => collect(KnowledgeUpdateQueue::PRIORITIES)->map(fn (string $priority) => [
                    'value' => $priority,
                    'label' => ucfirst($priority),
                ])->values(),
                'accountCategories' => collect(Prospect::ACCOUNT_CATEGORIES)->map(fn (string $category) => [
                    'value' => $category,
                    'label' => Prospect::ACCOUNT_CATEGORY_LABELS[$category] ?? strtoupper($category),
                ])->values(),
            ],
        ]);
    }

    public function meta(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'name' => $user->name,
                'role' => $user->role,
                'roleLabel' => $user->roleLabel(),
                'initials' => strtoupper(substr($user->name, 0, 1)),
            ],
            'navigation' => [
                ['label' => 'Dashboard', 'href' => '/dashboard'],
                ['label' => 'Lead', 'href' => '/prospects'],
                ['label' => 'Pipeline', 'href' => '/pipeline'],
                ['label' => 'Queue', 'href' => '/queue'],
                ['label' => 'WA WebView', 'href' => '/wa-webview'],
                ...($user->can('access-performance') ? [['label' => 'Command Center', 'href' => '/manager-insights']] : []),
                ...($user->can('access-performance') ? [['label' => 'Kinerja', 'href' => '/kinerja-penjualan']] : []),
                ...($user->can('access-chat-reviews') ? [['label' => 'Tinjauan Obrolan', 'href' => '/chat-reviews']] : []),
                ...($user->can('access-knowledge-queue') ? [['label' => 'Antrian Pengetahuan', 'href' => '/knowledge-queue']] : []),
                ...($user->can('manage-users') ? [['label' => 'Manajemen User', 'href' => '/users']] : []),
            ],
            'abilities' => [
                'accessPerformance' => $user->can('access-performance'),
                'accessChatReviews' => $user->can('access-chat-reviews'),
                'accessKnowledgeQueue' => $user->can('access-knowledge-queue'),
                'manageUsers' => $user->can('manage-users'),
                'manageSystemSettings' => $user->can('manage-system-settings'),
            ],
        ]);
    }

    private function prospectOperationalFields(Prospect $prospect): array
    {
        return [
            'aging_days' => $prospect->aging_days,
            'last_activity_diff' => $prospect->last_activity_diff,
            'is_stale' => $prospect->is_stale,
            'status_updated_at' => $prospect->status_updated_at?->toISOString(),
            'last_activity_at' => $prospect->last_activity_at?->toISOString(),
            'follow_up_state' => $prospect->follow_up_state,
            'priority_level' => $prospect->priority_level,
            'overdue_days' => $prospect->overdue_days,
        ];
    }

    private function actionLeadItem(Prospect $lead, array $extra = []): array
    {
        return [
            'id' => $lead->id,
            'prospectCode' => $lead->prospect_code,
            'name' => $lead->name,
            'status' => $lead->status,
            'statusLabel' => Prospect::STATUS_LABELS[$lead->status] ?? strtoupper($lead->status),
            'owner' => $lead->owner?->name ?? '-',
            'nextFollowUpDateLabel' => $lead->next_follow_up_date?->format('d M Y') ?: '-',
            'followUpState' => $lead->follow_up_state,
            'priorityLevel' => $lead->priority_level,
            'lastActivityDiff' => $lead->last_activity_diff,
            'detailUrl' => route('prospects.show', $lead),
            ...$extra,
        ];
    }

    private function nextActionLabel(?string $code): string
    {
        return match ($code) {
            'followup_overdue_now' => 'Follow up overdue sekarang',
            'send_social_proof' => 'Kirim social proof',
            'schedule_closing_call' => 'Jadwalkan closing call',
            'nudge_followup_message' => 'Kirim nudge follow up',
            'qualify_next_step' => 'Kualifikasi langkah berikutnya',
            default => 'Review manual',
        };
    }

    private function isSnapshotVisibleInQueue(LeadOperationalSnapshot $snapshot): bool
    {
        $state = $snapshot->queueState;
        if (! $state) {
            return true;
        }

        $fingerprint = implode(':', [
            $snapshot->id,
            $snapshot->version,
            $snapshot->next_action_code ?? 'none',
            $snapshot->priority_score,
        ]);

        if ($state->action_fingerprint !== $fingerprint) {
            return true;
        }

        if ($state->state === QueueActionLifecycleService::ACTION_DONE) {
            return false;
        }

        if ($state->state === QueueActionLifecycleService::ACTION_SNOOZE) {
            return ! $state->snoozed_until || $state->snoozed_until->lte(now());
        }

        if ($state->state === QueueActionLifecycleService::ACTION_DISMISS) {
            return ! $state->dismissed_until || $state->dismissed_until->lte(now());
        }

        return true;
    }

    private function objectionTypeOptions()
    {
        return collect(ProspectLog::OBJECTION_TYPES)->map(fn (string $type) => [
            'value' => $type,
            'label' => ProspectLog::OBJECTION_TYPE_LABELS[$type] ?? strtoupper($type),
        ])->values();
    }

    private function emotionalStateOptions()
    {
        return collect(ProspectLog::EMOTIONAL_STATES)->map(fn (string $state) => [
            'value' => $state,
            'label' => ProspectLog::EMOTIONAL_STATE_LABELS[$state] ?? strtoupper($state),
        ])->values();
    }

    private function activeFollowUpQuery(Builder $query): Builder
    {
        return $query->whereNotIn('status', [Prospect::STATUS_PENUTUPAN, Prospect::STATUS_HILANG]);
    }

    private function overdueFollowUpQuery(Builder $query): Builder
    {
        return $this->activeFollowUpQuery($query)
            ->whereDate('next_follow_up_date', '<', now()->toDateString());
    }

    private function dueTodayFollowUpQuery(Builder $query): Builder
    {
        return $this->activeFollowUpQuery($query)
            ->whereDate('next_follow_up_date', now()->toDateString());
    }

    private function highPriorityProspectsQuery(Builder $query): Builder
    {
        $today = now()->toDateString();
        $criticalCutoff = now()->subDays(2)->toDateString();

        return $this->activeFollowUpQuery($query)
            ->where(function (Builder $q) use ($today, $criticalCutoff) {
                $q->whereDate('next_follow_up_date', '<=', $today)
                    ->orWhereDate('next_follow_up_date', '<', $criticalCutoff);
            });
    }

    private function staleProspectsQuery(Builder $query): Builder
    {
        $cutoff = now()->subDays(3);

        return $query
            ->whereNotIn('status', [Prospect::STATUS_PENUTUPAN, Prospect::STATUS_HILANG])
            ->where(function (Builder $q) use ($cutoff) {
                $q->where('last_activity_at', '<', $cutoff)
                    ->orWhere(function (Builder $fallback) use ($cutoff) {
                        $fallback->whereNull('last_activity_at')
                            ->where('updated_at', '<', $cutoff);
                    });
            });
    }

    private function agingOverSevenDaysQuery(Builder $query): Builder
    {
        $cutoff = now()->subDays(7);

        return $query
            ->whereNotIn('status', [Prospect::STATUS_PENUTUPAN, Prospect::STATUS_HILANG])
            ->where(function (Builder $q) use ($cutoff) {
                $q->where('status_updated_at', '<', $cutoff)
                    ->orWhere(function (Builder $fallback) use ($cutoff) {
                        $fallback->whereNull('status_updated_at')
                            ->where('created_at', '<', $cutoff);
                    });
            });
    }

    private function scopedProspects(User $user): Builder
    {
        return Prospect::query()
            ->when($user->isPenjualan(), fn (Builder $q) => $q->where('owner_id', $user->id))
            ->when($user->isManager(), fn (Builder $q) => $q->where('team_id', $user->team_id))
            ->when($user->isKepala(), fn (Builder $q) => $q->where('unit_id', $user->unit_id));
    }

    private function scopeProspects(Builder $query, User $user): void
    {
        $query
            ->when($user->isPenjualan(), fn (Builder $q) => $q->where('owner_id', $user->id))
            ->when($user->isManager(), fn (Builder $q) => $q->where('team_id', $user->team_id))
            ->when($user->isKepala(), fn (Builder $q) => $q->where('unit_id', $user->unit_id));
    }

    private function scopedChatReviews(User $user): Builder
    {
        return ChatReview::query()
            ->when($user->isPenjualan(), fn (Builder $q) => $q->whereRaw('1 = 0'))
            ->when($user->isManager(), fn (Builder $q) => $q->whereHas('submitter', fn (Builder $inner) => $inner->where('team_id', $user->team_id)))
            ->when($user->isKepala(), fn (Builder $q) => $q->whereHas('submitter', fn (Builder $inner) => $inner->where('unit_id', $user->unit_id)));
    }

    private function scopedKnowledgeQueues(User $user): Builder
    {
        return KnowledgeUpdateQueue::query()
            ->when($user->isKepala(), fn (Builder $q) => $q->whereHas('chatReview.submitter', fn (Builder $inner) => $inner->where('unit_id', $user->unit_id)));
    }

    private function applyProspectFilters(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->string('status')->toString(), fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($request->integer('owner_id'), fn (Builder $q, int $ownerId) => $q->where('owner_id', $ownerId))
            ->when($request->string('account_category')->toString(), fn (Builder $q, string $accountCategory) => $q->where('account_category', $accountCategory))
            ->when($request->string('gpt_mode')->toString(), fn (Builder $q, string $gptMode) => $q->where('gpt_mode', $gptMode))
            ->when($request->string('user_temperature')->toString(), fn (Builder $q, string $temperature) => $q->where('user_temperature', $temperature))
            ->when($request->string('dominant_emotion')->toString(), fn (Builder $q, string $emotion) => $q->where('dominant_emotion', $emotion))
            ->when($request->string('bridge_candidate')->toString(), fn (Builder $q, string $bridgeCandidate) => $q->where('bridge_candidate', filter_var($bridgeCandidate, FILTER_VALIDATE_BOOLEAN)))
            ->when($request->string('bridge_status')->toString(), fn (Builder $q, string $bridgeStatus) => $q->where('bridge_status', $bridgeStatus))
            ->when($request->string('lost_reason')->toString(), fn (Builder $q, string $lostReason) => $q->where('lost_reason', $lostReason))
            ->when($request->string('q')->toString(), function (Builder $q, string $keyword) {
                $q->where(function (Builder $inner) use ($keyword) {
                    $inner->where('name', 'like', "%{$keyword}%")
                        ->orWhere('company', 'like', "%{$keyword}%")
                        ->orWhere('prospect_code', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%");
                });
            })
            ->when($request->string('follow_up')->toString(), function (Builder $q, string $followUpFilter) {
                if ($followUpFilter === 'overdue') {
                    $this->overdueFollowUpQuery($q);
                }

                if ($followUpFilter === 'today') {
                    $this->dueTodayFollowUpQuery($q);
                }

                if ($followUpFilter === 'week') {
                    $this->activeFollowUpQuery($q)
                        ->whereBetween('next_follow_up_date', [
                            now()->toDateString(),
                            now()->addDays(7)->toDateString(),
                        ]);
                }

                if ($followUpFilter === 'soon') {
                    $this->activeFollowUpQuery($q)
                        ->whereBetween('next_follow_up_date', [
                            now()->addDay()->toDateString(),
                            now()->addDays(2)->toDateString(),
                        ]);
                }

                if ($followUpFilter === 'stale') {
                    $this->staleProspectsQuery($q);
                }
            });
    }

    private function visibleSalesUsers(User $user)
    {
        return User::query()
            ->where('role', User::ROLE_PENJUALAN)
            ->when($user->isPenjualan(), fn (Builder $q) => $q->where('id', $user->id))
            ->when($user->isManager(), fn (Builder $q) => $q->where('team_id', $user->team_id))
            ->when($user->isKepala(), fn (Builder $q) => $q->where('unit_id', $user->unit_id))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function currentFilters(Request $request): array
    {
        return [
            'q' => $request->string('q')->toString(),
            'account_category' => $request->string('account_category')->toString(),
            'status' => $request->string('status')->toString(),
            'owner_id' => $request->string('owner_id')->toString(),
            'follow_up' => $request->string('follow_up')->toString(),
            'gpt_mode' => $request->string('gpt_mode')->toString(),
            'user_temperature' => $request->string('user_temperature')->toString(),
            'dominant_emotion' => $request->string('dominant_emotion')->toString(),
            'bridge_candidate' => $request->string('bridge_candidate')->toString(),
            'bridge_status' => $request->string('bridge_status')->toString(),
            'lost_reason' => $request->string('lost_reason')->toString(),
        ];
    }

    private function filterOptions(User $user): array
    {
        return [
            'accountCategories' => collect(Prospect::ACCOUNT_CATEGORIES)->map(fn (string $category) => [
                'value' => $category,
                'label' => Prospect::ACCOUNT_CATEGORY_LABELS[$category] ?? strtoupper($category),
            ])->values(),
            'gptModes' => collect(Prospect::GPT_MODES)->map(fn (string $mode) => [
                'value' => $mode,
                'label' => Prospect::GPT_MODE_LABELS[$mode] ?? strtoupper($mode),
            ])->values(),
            'userTemperatures' => collect(Prospect::USER_TEMPERATURES)->map(fn (string $temperature) => [
                'value' => $temperature,
                'label' => Prospect::USER_TEMPERATURE_LABELS[$temperature] ?? strtoupper($temperature),
            ])->values(),
            'dominantEmotions' => collect(Prospect::DOMINANT_EMOTIONS)->map(fn (string $emotion) => [
                'value' => $emotion,
                'label' => Prospect::DOMINANT_EMOTION_LABELS[$emotion] ?? strtoupper($emotion),
            ])->values(),
            'bridgeStatuses' => collect(Prospect::BRIDGE_STATUSES)->map(fn (string $status) => [
                'value' => $status,
                'label' => Prospect::BRIDGE_STATUS_LABELS[$status] ?? strtoupper($status),
            ])->values(),
            'lostReasons' => collect(Prospect::LOST_REASONS)->map(fn (string $reason) => [
                'value' => $reason,
                'label' => Prospect::LOST_REASON_LABELS[$reason] ?? strtoupper($reason),
            ])->values(),
            'salesUsers' => $this->visibleSalesUsers($user)->map(fn (User $sales) => [
                'value' => (string) $sales->id,
                'label' => $sales->name,
            ])->values(),
        ];
    }
}
