<?php

namespace App\Services;

use App\Models\Prospect;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ManagerInsightService
{
    public function __construct(
        private readonly SalesDisciplineMetricsService $disciplineMetrics,
        private readonly ObjectionAnalyticsService $objectionAnalytics,
    ) {
    }

    public function insights(User $user, Builder $prospectsQuery, Collection $salesUsers): array
    {
        $activeStatuses = [
            Prospect::STATUS_BARU,
            Prospect::STATUS_DIHUBUNGI,
            Prospect::STATUS_DIBALAS,
            Prospect::STATUS_SEDANG_BERJALAN,
            Prospect::STATUS_TINDAK_LANJUT,
        ];

        $activeProspects = (clone $prospectsQuery)
            ->with('owner:id,name')
            ->whereIn('status', $activeStatuses)
            ->get([
                'id',
                'prospect_code',
                'name',
                'owner_id',
                'account_category',
                'status',
                'user_temperature',
                'estimation_value',
                'next_follow_up_date',
                'last_activity_at',
                'status_updated_at',
                'created_at',
                'updated_at',
            ]);

        $discipline = collect($this->disciplineMetrics->forSales($salesUsers, clone $prospectsQuery))->values();
        $objections = $this->objectionAnalytics->insights(clone $prospectsQuery);

        $activeLeadCount = $activeProspects->count();
        $overdueLeads = $activeProspects->filter(fn (Prospect $prospect) => $prospect->follow_up_state === Prospect::FOLLOW_UP_STATE_OVERDUE);
        $staleLeads = $activeProspects->filter(fn (Prospect $prospect) => $prospect->is_stale);
        $dueTodayLeads = $activeProspects->filter(fn (Prospect $prospect) => $prospect->follow_up_state === Prospect::FOLLOW_UP_STATE_TODAY);
        $stuckLeads = $activeProspects->filter(fn (Prospect $prospect) => $prospect->aging_days > 7);
        $inactiveSales = $discipline->filter(fn (array $metric) => $metric['daily_activity_count'] === 0 && $metric['active_lead_count'] > 0);

        $teamHealth = [
            'totalActiveLeads' => $activeLeadCount,
            'overdueLeads' => $overdueLeads->count(),
            'staleLeads' => $staleLeads->count(),
            'dueToday' => $dueTodayLeads->count(),
            'activeSalesToday' => $discipline->filter(fn (array $metric) => $metric['daily_activity_count'] > 0)->count(),
            'inactiveSalesToday' => $inactiveSales->count(),
            'avgFollowUpCompliance' => $discipline->count() > 0 ? round($discipline->avg('follow_up_compliance_rate'), 1) : 100,
            'avgCrmActivityScore' => $discipline->count() > 0 ? round($discipline->avg('crm_activity_score'), 1) : 100,
        ];

        return [
            'teamHealth' => $teamHealth,
            'alerts' => $this->alerts($overdueLeads, $inactiveSales, $stuckLeads, $objections),
            'salesRanking' => [
                'topDisciplined' => $discipline
                    ->sortByDesc(fn (array $metric) => [$metric['crm_activity_score'], $metric['follow_up_compliance_rate'], $metric['daily_activity_count']])
                    ->take(5)
                    ->values(),
                'needsAttention' => $discipline
                    ->sortBy(fn (array $metric) => [$metric['crm_activity_score'], -$metric['overdue_ratio'], -$metric['stale_lead_ratio']])
                    ->take(5)
                    ->values(),
            ],
            'pipelineBottleneck' => $this->pipelineBottleneck($activeProspects),
            'priorityLeads' => $this->priorityLeads($activeProspects),
            'objectionTrends' => [
                'mostCommon' => $objections['managerInsights']['mostCommonThisWeek'] ?? null,
                'worstConversion' => $objections['managerInsights']['lowestConversion'] ?? null,
                'trendingUp' => collect($objections['topObjections'] ?? [])->take(3)->values(),
            ],
        ];
    }

    private function alerts(Collection $overdueLeads, Collection $inactiveSales, Collection $stuckLeads, array $objections): Collection
    {
        $alerts = collect();
        $criticalOverdue = $overdueLeads->filter(fn (Prospect $prospect) => $prospect->overdue_days > 3)->count();

        if ($criticalOverdue > 0) {
            $alerts->push([
                'level' => 'critical',
                'message' => "{$criticalOverdue} lead overdue > 3 hari",
            ]);
        }

        if ($inactiveSales->count() > 0) {
            $alerts->push([
                'level' => 'warning',
                'message' => $inactiveSales->count().' sales belum ada activity hari ini',
            ]);
        }

        if ($stuckLeads->count() > 0) {
            $alerts->push([
                'level' => 'warning',
                'message' => $stuckLeads->count().' lead stuck > 7 hari',
            ]);
        }

        $hardest = $objections['managerInsights']['lowestConversion'] ?? null;
        if ($hardest && ($hardest['conversionRate'] ?? 100) < 30) {
            $alerts->push([
                'level' => 'critical',
                'message' => 'Conversion turun pada objection '.$hardest['label'],
            ]);
        }

        if ($alerts->isEmpty()) {
            $alerts->push([
                'level' => 'healthy',
                'message' => 'Tidak ada alert operasional besar hari ini',
            ]);
        }

        return $alerts->values();
    }

    private function pipelineBottleneck(Collection $activeProspects): Collection
    {
        $total = max(1, $activeProspects->count());

        return $activeProspects
            ->groupBy('status')
            ->map(fn (Collection $items, string $status) => [
                'status' => $status,
                'label' => Prospect::STATUS_LABELS[$status] ?? ucfirst($status),
                'total' => $items->count(),
                'stuckCount' => $items->filter(fn (Prospect $prospect) => $prospect->aging_days > 7)->count(),
                'percent' => round(($items->count() / $total) * 100, 1),
            ])
            ->sortByDesc('total')
            ->values();
    }

    private function priorityLeads(Collection $activeProspects): Collection
    {
        return $activeProspects
            ->filter(function (Prospect $prospect) {
                return $prospect->priority_level === Prospect::PRIORITY_LEVEL_CRITICAL
                    || ($prospect->is_stale && $prospect->user_temperature === 'hot')
                    || ((float) $prospect->estimation_value >= 10000000)
                    || ($prospect->account_category === 'reguler' && $prospect->is_stale);
            })
            ->sortBy([
                fn (Prospect $a, Prospect $b) => $b->overdue_days <=> $a->overdue_days,
                fn (Prospect $a, Prospect $b) => ((float) $b->estimation_value) <=> ((float) $a->estimation_value),
            ])
            ->take(10)
            ->map(fn (Prospect $prospect) => [
                'id' => $prospect->id,
                'prospectCode' => $prospect->prospect_code,
                'name' => $prospect->name,
                'owner' => $prospect->owner?->name ?? '-',
                'status' => $prospect->status,
                'statusLabel' => Prospect::STATUS_LABELS[$prospect->status] ?? ucfirst($prospect->status),
                'overdueDays' => $prospect->overdue_days,
                'nextFollowUpDateLabel' => $prospect->next_follow_up_date?->format('d M Y') ?: '-',
                'priorityLevel' => $prospect->priority_level,
                'detailUrl' => route('prospects.show', $prospect),
            ])
            ->values();
    }
}
