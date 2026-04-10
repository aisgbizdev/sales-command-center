<?php

namespace App\Http\Controllers;

use App\Models\Prospect;
use App\Models\ProspectLog;
use App\Models\User;
use App\Support\RoleScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProspectController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Prospect::class);
        $user = $request->user();
        $prospects = $this->applyFilters(
            $this->scopedProspects($user)->with(['owner:id,name', 'team:id,name', 'unit:id,name']),
            $request
        )
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('prospects.index', [
            'prospects' => $prospects,
            'statuses' => Prospect::STATUSES,
            'statusLabels' => Prospect::STATUS_LABELS,
            'accountCategories' => Prospect::ACCOUNT_CATEGORIES,
            'accountCategoryLabels' => Prospect::ACCOUNT_CATEGORY_LABELS,
            'gptModes' => Prospect::GPT_MODES,
            'gptModeLabels' => Prospect::GPT_MODE_LABELS,
            'userTemperatures' => Prospect::USER_TEMPERATURES,
            'userTemperatureLabels' => Prospect::USER_TEMPERATURE_LABELS,
            'dominantEmotions' => Prospect::DOMINANT_EMOTIONS,
            'dominantEmotionLabels' => Prospect::DOMINANT_EMOTION_LABELS,
            'bridgeStatuses' => Prospect::BRIDGE_STATUSES,
            'bridgeStatusLabels' => Prospect::BRIDGE_STATUS_LABELS,
            'lostReasons' => Prospect::LOST_REASONS,
            'lostReasonLabels' => Prospect::LOST_REASON_LABELS,
            'salesUsers' => $this->visibleSalesUsers($user),
            'canCreateProspect' => $user->canCreateProspect(),
        ]);
    }

    public function pipeline(Request $request)
    {
        $this->authorize('viewAny', Prospect::class);
        $query = $this->applyFilters(
            $this->scopedProspects($request->user())->with(['owner:id,name', 'team:id,name']),
            $request
        );

        $prospects = $query
            ->orderBy('priority')
            ->orderBy('next_follow_up_date')
            ->orderByDesc('updated_at')
            ->get();

        $pipeline = collect(Prospect::STATUSES)
            ->mapWithKeys(fn (string $status) => [$status => $prospects->where('status', $status)->values()]);

        $today = now()->toDateString();
        $overdueCount = $prospects
            ->where('status', '!=', Prospect::STATUS_HILANG)
            ->filter(fn (Prospect $prospect) => $prospect->next_follow_up_date && $prospect->next_follow_up_date->isBefore($today))
            ->count();

        $dueTodayCount = $prospects
            ->where('status', '!=', Prospect::STATUS_HILANG)
            ->filter(fn (Prospect $prospect) => $prospect->next_follow_up_date && $prospect->next_follow_up_date->isSameDay($today))
            ->count();

        $dueSoonCount = $prospects
            ->where('status', '!=', Prospect::STATUS_HILANG)
            ->filter(function (Prospect $prospect) use ($today) {
                if (! $prospect->next_follow_up_date) {
                    return false;
                }

                return $prospect->next_follow_up_date->isAfter($today)
                    && $prospect->next_follow_up_date->lessThanOrEqualTo(now()->addDays(3)->toDateString());
            })
            ->count();

        return view('prospects.pipeline', [
            'pipeline' => $pipeline,
            'statuses' => Prospect::STATUSES,
            'statusLabels' => Prospect::STATUS_LABELS,
            'accountCategories' => Prospect::ACCOUNT_CATEGORIES,
            'accountCategoryLabels' => Prospect::ACCOUNT_CATEGORY_LABELS,
            'gptModes' => Prospect::GPT_MODES,
            'gptModeLabels' => Prospect::GPT_MODE_LABELS,
            'userTemperatures' => Prospect::USER_TEMPERATURES,
            'userTemperatureLabels' => Prospect::USER_TEMPERATURE_LABELS,
            'dominantEmotions' => Prospect::DOMINANT_EMOTIONS,
            'dominantEmotionLabels' => Prospect::DOMINANT_EMOTION_LABELS,
            'bridgeStatuses' => Prospect::BRIDGE_STATUSES,
            'bridgeStatusLabels' => Prospect::BRIDGE_STATUS_LABELS,
            'lostReasons' => Prospect::LOST_REASONS,
            'lostReasonLabels' => Prospect::LOST_REASON_LABELS,
            'salesUsers' => $this->visibleSalesUsers($request->user()),
            'overdueCount' => $overdueCount,
            'dueTodayCount' => $dueTodayCount,
            'dueSoonCount' => $dueSoonCount,
        ]);
    }

    public function performance(Request $request)
    {
        abort_unless($request->user()->can('access-performance'), 403);
        $user = $request->user();
        $from = $request->date('from')?->startOfDay() ?? now()->startOfMonth();
        $to = $request->date('to')?->endOfDay() ?? now()->endOfDay();
        $accountCategory = $request->string('account_category')->toString();

        $salesUsers = User::query()
            ->where('role', User::ROLE_PENJUALAN)
            ->when($user->isPenjualan(), fn (Builder $q) => $q->where('id', $user->id))
            ->when($user->isManager(), fn (Builder $q) => $q->where('team_id', $user->team_id))
            ->when($user->isKepala(), fn (Builder $q) => $q->where('unit_id', $user->unit_id))
            ->withCount([
                'prospects as total_prospects' => fn (Builder $q) => $q->whereBetween('created_at', [$from, $to])->when($accountCategory, fn (Builder $inner) => $inner->where('account_category', $accountCategory)),
                'prospects as closing_count' => fn (Builder $q) => $q->where('status', Prospect::STATUS_PENUTUPAN)->whereBetween('created_at', [$from, $to])->when($accountCategory, fn (Builder $inner) => $inner->where('account_category', $accountCategory)),
                'prospects as lost_count' => fn (Builder $q) => $q->where('status', Prospect::STATUS_HILANG)->whereBetween('created_at', [$from, $to])->when($accountCategory, fn (Builder $inner) => $inner->where('account_category', $accountCategory)),
                'prospects as overdue_count' => fn (Builder $q) => $q
                    ->where('status', '!=', Prospect::STATUS_HILANG)
                    ->when($accountCategory, fn (Builder $inner) => $inner->where('account_category', $accountCategory))
                    ->whereDate('next_follow_up_date', '<', now()->toDateString()),
                'prospectLogs as activity_count' => fn (Builder $q) => $q->whereBetween('log_date', [$from->toDateString(), $to->toDateString()]),
            ])
            ->withSum([
                'prospects as total_value' => fn (Builder $q) => $q->whereBetween('created_at', [$from, $to])->when($accountCategory, fn (Builder $inner) => $inner->where('account_category', $accountCategory)),
            ], 'estimation_value')
            ->orderByDesc('closing_count')
            ->orderByDesc('activity_count')
            ->get();

        $scopeProspects = $this->scopedProspects($user)->when($accountCategory, fn (Builder $q) => $q->where('account_category', $accountCategory));
        $statusBreakdown = (clone $scopeProspects)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $overdueProspects = (clone $scopeProspects)
            ->with('owner:id,name')
            ->where('status', '!=', Prospect::STATUS_HILANG)
            ->whereDate('next_follow_up_date', '<', now()->toDateString())
            ->orderBy('next_follow_up_date')
            ->limit(10)
            ->get();

        return view('prospects.performance', [
            'salesUsers' => $salesUsers,
            'statusBreakdown' => $statusBreakdown,
            'statusLabels' => Prospect::STATUS_LABELS,
            'accountCategories' => Prospect::ACCOUNT_CATEGORIES,
            'accountCategoryLabels' => Prospect::ACCOUNT_CATEGORY_LABELS,
            'gptModes' => Prospect::GPT_MODES,
            'gptModeLabels' => Prospect::GPT_MODE_LABELS,
            'userTemperatures' => Prospect::USER_TEMPERATURES,
            'userTemperatureLabels' => Prospect::USER_TEMPERATURE_LABELS,
            'dominantEmotions' => Prospect::DOMINANT_EMOTIONS,
            'dominantEmotionLabels' => Prospect::DOMINANT_EMOTION_LABELS,
            'bridgeStatuses' => Prospect::BRIDGE_STATUSES,
            'bridgeStatusLabels' => Prospect::BRIDGE_STATUS_LABELS,
            'lostReasons' => Prospect::LOST_REASONS,
            'lostReasonLabels' => Prospect::LOST_REASON_LABELS,
            'from' => $from,
            'to' => $to,
            'selectedAccountCategory' => $accountCategory,
            'overdueProspects' => $overdueProspects,
        ]);
    }

    public function quickUpdate(Request $request, Prospect $prospect)
    {
        $user = $request->user();
        $this->authorize('update', $prospect);

        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', Prospect::STATUSES)],
            'next_follow_up_date' => ['nullable', 'date'],
            'quick_note' => ['nullable', 'string', 'max:200'],
            'user_temperature' => ['nullable', 'in:'.implode(',', Prospect::USER_TEMPERATURES)],
            'dominant_emotion' => ['nullable', 'in:'.implode(',', Prospect::DOMINANT_EMOTIONS)],
            'main_objection' => ['nullable', 'string'],
            'bridge_candidate' => ['nullable', 'boolean'],
        ]);

        $oldLabel = Prospect::STATUS_LABELS[$prospect->status] ?? $prospect->status;
        $newLabel = Prospect::STATUS_LABELS[$validated['status']] ?? $validated['status'];
        $quickNote = $validated['quick_note'] ?? null;

        $prospect->update([
            'status' => $validated['status'],
            'next_follow_up_date' => $validated['next_follow_up_date'] ?? null,
            'user_temperature' => $validated['user_temperature'] ?? $prospect->user_temperature,
            'dominant_emotion' => $validated['dominant_emotion'] ?? $prospect->dominant_emotion,
            'main_objection' => array_key_exists('main_objection', $validated) ? $validated['main_objection'] : $prospect->main_objection,
            'bridge_candidate' => (bool) ($validated['bridge_candidate'] ?? $prospect->bridge_candidate),
            'bridge_status' => ($validated['bridge_candidate'] ?? $prospect->bridge_candidate) ? ($prospect->bridge_status === 'none' ? 'identified' : $prospect->bridge_status) : $prospect->bridge_status,
            'last_contact_at' => now(),
        ]);

        ProspectLog::create([
            'log_date' => now()->toDateString(),
            'activity_type' => 'follow_up',
            'summary' => filled($quickNote) ? $quickNote : "Status diubah: {$oldLabel} -> {$newLabel}",
            'result' => null,
            'gpt_used' => false,
            'gpt_mode' => $prospect->gpt_mode,
            'chat_outcome_type' => $this->mapStatusToChatOutcomeType($validated['status']),
            'objection_snapshot' => $validated['main_objection'] ?? $prospect->main_objection,
            'next_follow_up_date' => $validated['next_follow_up_date'] ?? null,
            'prospect_id' => $prospect->id,
            'user_id' => $user->id,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Prospek berhasil diperbarui cepat.',
            ]);
        }

        return back()->with('status', 'Prospek berhasil diperbarui cepat.');
    }

    public function create(Request $request)
    {
        $this->authorize('create', Prospect::class);

        return view('prospects.create', [
            'statuses' => Prospect::STATUSES,
            'statusLabels' => Prospect::STATUS_LABELS,
            'accountCategories' => Prospect::ACCOUNT_CATEGORIES,
            'accountCategoryLabels' => Prospect::ACCOUNT_CATEGORY_LABELS,
            'gptModes' => Prospect::GPT_MODES,
            'gptModeLabels' => Prospect::GPT_MODE_LABELS,
            'userTemperatures' => Prospect::USER_TEMPERATURES,
            'userTemperatureLabels' => Prospect::USER_TEMPERATURE_LABELS,
            'dominantEmotions' => Prospect::DOMINANT_EMOTIONS,
            'dominantEmotionLabels' => Prospect::DOMINANT_EMOTION_LABELS,
            'bridgeStatuses' => Prospect::BRIDGE_STATUSES,
            'bridgeStatusLabels' => Prospect::BRIDGE_STATUS_LABELS,
            'lostReasons' => Prospect::LOST_REASONS,
            'lostReasonLabels' => Prospect::LOST_REASON_LABELS,
            'sources' => Prospect::SOURCES,
            'types' => ProspectLog::TYPES,
            'salesUsers' => $this->visibleSalesUsers($request->user()),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $this->authorize('create', Prospect::class);
        $validated = $this->validateProspectPayload($request);
        $owner = $this->resolveOwner($user, $validated['owner_id'] ?? null);

        $prospectPayload = $this->buildProspectPayload($validated, $owner);

        $prospect = Prospect::create([
            'prospect_code' => 'PR-'.now()->format('Ymd').'-'.str_pad((string) (Prospect::whereDate('created_at', now()->toDateString())->count() + 1), 4, '0', STR_PAD_LEFT),
            ...$prospectPayload,
        ]);

        $this->saveDailyLogIfExists($validated, $prospect, $user);

        return redirect()->route('prospects.index')->with('status', 'Prospek berhasil ditambahkan.');
    }

    public function show(Request $request, Prospect $prospect)
    {
        $user = $request->user();
        $this->authorize('view', $prospect);

        return view('prospects.show', [
            'prospect' => $prospect->load(['owner:id,name', 'team:id,name', 'unit:id,name', 'logs.user:id,name']),
            'types' => ProspectLog::TYPES,
            'statusLabels' => Prospect::STATUS_LABELS,
            'canEditProspect' => $user->canEditProspect($prospect),
        ]);
    }

    public function edit(Request $request, Prospect $prospect)
    {
        $user = $request->user();
        $this->authorize('update', $prospect);

        return view('prospects.edit', [
            'prospect' => $prospect,
            'statuses' => Prospect::STATUSES,
            'statusLabels' => Prospect::STATUS_LABELS,
            'accountCategories' => Prospect::ACCOUNT_CATEGORIES,
            'accountCategoryLabels' => Prospect::ACCOUNT_CATEGORY_LABELS,
            'gptModes' => Prospect::GPT_MODES,
            'gptModeLabels' => Prospect::GPT_MODE_LABELS,
            'userTemperatures' => Prospect::USER_TEMPERATURES,
            'userTemperatureLabels' => Prospect::USER_TEMPERATURE_LABELS,
            'dominantEmotions' => Prospect::DOMINANT_EMOTIONS,
            'dominantEmotionLabels' => Prospect::DOMINANT_EMOTION_LABELS,
            'bridgeStatuses' => Prospect::BRIDGE_STATUSES,
            'bridgeStatusLabels' => Prospect::BRIDGE_STATUS_LABELS,
            'lostReasons' => Prospect::LOST_REASONS,
            'lostReasonLabels' => Prospect::LOST_REASON_LABELS,
            'sources' => Prospect::SOURCES,
            'types' => ProspectLog::TYPES,
            'salesUsers' => $this->visibleSalesUsers($request->user()),
        ]);
    }

    public function update(Request $request, Prospect $prospect)
    {
        $user = $request->user();
        $this->authorize('update', $prospect);

        $validated = $this->validateProspectPayload($request);
        $owner = $this->resolveOwner($user, $validated['owner_id'] ?? $prospect->owner_id);

        $prospect->update($this->buildProspectPayload($validated, $owner, $prospect));

        $this->saveDailyLogIfExists($validated, $prospect, $user);

        return redirect()->route('prospects.show', $prospect)->with('status', 'Prospek berhasil diperbarui.');
    }

    public function destroy(Request $request, Prospect $prospect)
    {
        $user = $request->user();
        $this->authorize('delete', $prospect);
        $prospect->delete();

        return redirect()->route('prospects.index')->with('status', 'Prospek berhasil dihapus.');
    }

    private function validateProspectPayload(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'source' => ['nullable', 'in:'.implode(',', Prospect::SOURCES)],
            'account_category' => ['required', 'in:'.implode(',', Prospect::ACCOUNT_CATEGORIES)],
            'user_temperature' => ['nullable', 'in:'.implode(',', Prospect::USER_TEMPERATURES)],
            'dominant_emotion' => ['nullable', 'in:'.implode(',', Prospect::DOMINANT_EMOTIONS)],
            'main_objection' => ['nullable', 'string'],
            'gpt_mode' => ['nullable', 'in:'.implode(',', Prospect::GPT_MODES)],
            'bridge_candidate' => ['nullable', 'boolean'],
            'bridge_status' => ['required', 'in:'.implode(',', Prospect::BRIDGE_STATUSES)],
            'lost_reason' => ['nullable', 'in:'.implode(',', Prospect::LOST_REASONS)],
            'status' => ['required', 'in:'.implode(',', Prospect::STATUSES)],
            'priority' => ['required', 'integer', 'min:1', 'max:3'],
            'estimation_value' => ['nullable', 'numeric', 'min:0'],
            'next_follow_up_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'daily_activity_type' => ['nullable', 'in:'.implode(',', ProspectLog::TYPES)],
            'daily_summary' => ['nullable', 'string', 'max:200'],
            'daily_result' => ['nullable', 'string'],
        ]);
    }

    private function saveDailyLogIfExists(array $validated, Prospect $prospect, User $user): void
    {
        if (empty($validated['daily_summary']) || empty($validated['daily_activity_type'])) {
            return;
        }

        ProspectLog::create([
            'log_date' => now()->toDateString(),
            'activity_type' => $validated['daily_activity_type'],
            'summary' => $validated['daily_summary'],
            'result' => $validated['daily_result'] ?? null,
            'gpt_used' => false,
            'gpt_mode' => $prospect->gpt_mode,
            'chat_outcome_type' => $this->mapStatusToChatOutcomeType($prospect->status),
            'objection_snapshot' => $prospect->main_objection,
            'next_follow_up_date' => $validated['next_follow_up_date'] ?? null,
            'prospect_id' => $prospect->id,
            'user_id' => $user->id,
        ]);
    }

    private function applyFilters(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->string('status')->toString(), function (Builder $q, string $status) {
                $q->where('status', $status);
            })
            ->when($request->integer('owner_id'), function (Builder $q, int $ownerId) {
                $q->where('owner_id', $ownerId);
            })
            ->when($request->string('account_category')->toString(), function (Builder $q, string $accountCategory) {
                $q->where('account_category', $accountCategory);
            })
            ->when($request->string('gpt_mode')->toString(), function (Builder $q, string $gptMode) {
                $q->where('gpt_mode', $gptMode);
            })
            ->when($request->string('user_temperature')->toString(), function (Builder $q, string $temperature) {
                $q->where('user_temperature', $temperature);
            })
            ->when($request->string('dominant_emotion')->toString(), function (Builder $q, string $emotion) {
                $q->where('dominant_emotion', $emotion);
            })
            ->when($request->string('bridge_candidate')->toString(), function (Builder $q, string $bridgeCandidate) {
                $q->where('bridge_candidate', filter_var($bridgeCandidate, FILTER_VALIDATE_BOOLEAN));
            })
            ->when($request->string('bridge_status')->toString(), function (Builder $q, string $bridgeStatus) {
                $q->where('bridge_status', $bridgeStatus);
            })
            ->when($request->string('lost_reason')->toString(), function (Builder $q, string $lostReason) {
                $q->where('lost_reason', $lostReason);
            })
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
                    $q->where('status', '!=', Prospect::STATUS_HILANG)
                        ->whereDate('next_follow_up_date', '<', now()->toDateString());
                }

                if ($followUpFilter === 'today') {
                    $q->where('status', '!=', Prospect::STATUS_HILANG)
                        ->whereDate('next_follow_up_date', now()->toDateString());
                }

                if ($followUpFilter === 'week') {
                    $q->where('status', '!=', Prospect::STATUS_HILANG)
                        ->whereBetween('next_follow_up_date', [
                            now()->toDateString(),
                            now()->addDays(7)->toDateString(),
                        ]);
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
            ->get(['id', 'name', 'unit_id', 'team_id']);
    }

    private function scopedProspects(User $user): Builder
    {
        return RoleScope::forOwnerTeamUnit(Prospect::query(), $user);
    }

    private function buildProspectPayload(array $validated, User $owner, ?Prospect $existingProspect = null): array
    {
        $accountCategory = $validated['account_category'];
        $defaultGptMode = $accountCategory === 'mini' ? 'mini' : 'regular';
        $existingCategory = $existingProspect?->account_category;
        $isBridgeMoved = $existingCategory === 'mini' && $accountCategory === 'reguler';
        $bridgeCandidate = (bool) ($validated['bridge_candidate'] ?? $existingProspect?->bridge_candidate ?? false);
        $bridgeStatus = $validated['bridge_status'] ?? $existingProspect?->bridge_status ?? 'none';
        $lastContactAt = (! empty($validated['daily_summary']) || ! empty($validated['daily_result']))
            ? now()
            : $existingProspect?->last_contact_at;

        if ($isBridgeMoved) {
            $bridgeCandidate = true;
            $bridgeStatus = 'moved';
        } elseif ($bridgeCandidate && $bridgeStatus === 'none') {
            $bridgeStatus = 'identified';
        } elseif (! $bridgeCandidate && $bridgeStatus === 'identified') {
            $bridgeStatus = 'none';
        }

        return [
            'name' => $validated['name'],
            'company' => $validated['company'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'source' => $validated['source'] ?? null,
            'account_category' => $accountCategory,
            'user_temperature' => $validated['user_temperature'] ?? null,
            'dominant_emotion' => $validated['dominant_emotion'] ?? null,
            'main_objection' => $validated['main_objection'] ?? null,
            'gpt_mode' => $validated['gpt_mode'] ?? $defaultGptMode,
            'bridge_candidate' => $bridgeCandidate,
            'bridge_status' => $bridgeStatus,
            'lost_reason' => $validated['lost_reason'] ?? null,
            'last_contact_at' => $lastContactAt,
            'status' => $validated['status'],
            'priority' => $validated['priority'],
            'estimation_value' => $validated['estimation_value'] ?? 0,
            'next_follow_up_date' => $validated['next_follow_up_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'owner_id' => $owner->id,
            'team_id' => $owner->team_id,
            'unit_id' => $owner->unit_id,
        ];
    }

    private function mapStatusToChatOutcomeType(string $status): ?string
    {
        return match ($status) {
            Prospect::STATUS_DIBALAS => 'reply',
            Prospect::STATUS_SEDANG_BERJALAN, Prospect::STATUS_TINDAK_LANJUT => 'ongoing',
            Prospect::STATUS_PENUTUPAN => 'closing',
            Prospect::STATUS_HILANG => 'lost',
            default => null,
        };
    }

    private function resolveOwner(User $actor, ?int $requestedOwnerId): User
    {
        if ($actor->isPenjualan()) {
            return $actor;
        }

        $owner = User::query()
            ->where('id', $requestedOwnerId ?? 0)
            ->where('role', User::ROLE_PENJUALAN)
            ->first();

        if (! $owner) {
            abort(422, 'Owner prospek wajib user penjualan yang valid.');
        }

        if ($actor->isManager() && $owner->team_id !== $actor->team_id) {
            abort(403);
        }

        if ($actor->isKepala() && $owner->unit_id !== $actor->unit_id) {
            abort(403);
        }

        return $owner;
    }
}
