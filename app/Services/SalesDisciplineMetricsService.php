<?php

namespace App\Services;

use App\Models\Prospect;
use App\Models\ProspectLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SalesDisciplineMetricsService
{
    /**
     * @param  Collection<int, User>  $salesUsers
     * @return array<int, array<string, float|int|string>>
     */
    public function forSales(Collection $salesUsers, Builder $prospectsQuery): array
    {
        $salesIds = $salesUsers->pluck('id')->values();

        if ($salesIds->isEmpty()) {
            return [];
        }

        $today = now()->startOfDay();
        $activeStatuses = [
            Prospect::STATUS_BARU,
            Prospect::STATUS_DIHUBUNGI,
            Prospect::STATUS_DIBALAS,
            Prospect::STATUS_SEDANG_BERJALAN,
            Prospect::STATUS_TINDAK_LANJUT,
        ];

        $prospects = (clone $prospectsQuery)
            ->whereIn('owner_id', $salesIds)
            ->whereIn('status', $activeStatuses)
            ->get(['id', 'owner_id', 'next_follow_up_date', 'last_activity_at', 'updated_at']);

        $todayActivity = ProspectLog::query()
            ->selectRaw('user_id, COUNT(*) as total')
            ->whereIn('user_id', $salesIds)
            ->whereDate('log_date', $today->toDateString())
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $byOwner = $prospects->groupBy('owner_id');

        return $salesUsers->mapWithKeys(function (User $sales) use ($byOwner, $todayActivity, $today) {
            $items = $byOwner->get($sales->id, collect());
            $activeLeadCount = $items->count();
            $followUpLeadCount = $items->filter(fn (Prospect $prospect) => filled($prospect->next_follow_up_date))->count();
            $overdueItems = $items->filter(fn (Prospect $prospect) => $prospect->next_follow_up_date?->copy()->startOfDay()->lt($today));
            $staleItems = $items->filter(fn (Prospect $prospect) => (bool) $prospect->is_stale);
            $onTimeFollowUpCount = max(0, $followUpLeadCount - $overdueItems->count());
            $avgUpdateDelayHours = $overdueItems->count() > 0
                ? round($overdueItems->avg(fn (Prospect $prospect) => $prospect->next_follow_up_date->copy()->startOfDay()->diffInHours($today)), 1)
                : 0.0;

            $followUpComplianceRate = $followUpLeadCount > 0
                ? round(($onTimeFollowUpCount / $followUpLeadCount) * 100, 1)
                : 100.0;
            $overdueRatio = $activeLeadCount > 0
                ? round(($overdueItems->count() / $activeLeadCount) * 100, 1)
                : 0.0;
            $staleLeadRatio = $activeLeadCount > 0
                ? round(($staleItems->count() / $activeLeadCount) * 100, 1)
                : 0.0;
            $dailyActivityCount = (int) ($todayActivity[$sales->id] ?? 0);
            $crmActivityScore = $this->score(
                $followUpComplianceRate,
                $overdueRatio,
                $staleLeadRatio,
                $avgUpdateDelayHours,
                $dailyActivityCount,
                $activeLeadCount
            );

            return [
                $sales->id => [
                    'sales_id' => $sales->id,
                    'sales_name' => $sales->name,
                    'active_lead_count' => $activeLeadCount,
                    'follow_up_lead_count' => $followUpLeadCount,
                    'overdue_lead_count' => $overdueItems->count(),
                    'stale_lead_count' => $staleItems->count(),
                    'follow_up_compliance_rate' => $followUpComplianceRate,
                    'overdue_ratio' => $overdueRatio,
                    'stale_lead_ratio' => $staleLeadRatio,
                    'avg_update_delay_hours' => $avgUpdateDelayHours,
                    'crm_activity_score' => $crmActivityScore,
                    'daily_activity_count' => $dailyActivityCount,
                    'health_state' => $this->healthState($crmActivityScore),
                ],
            ];
        })->all();
    }

    private function score(
        float $followUpComplianceRate,
        float $overdueRatio,
        float $staleLeadRatio,
        float $avgUpdateDelayHours,
        int $dailyActivityCount,
        int $activeLeadCount
    ): int {
        $score = 100;
        $score -= min(30, (int) round($overdueRatio * 0.4));
        $score -= min(25, (int) round($staleLeadRatio * 0.35));

        if ($activeLeadCount > 0 && $dailyActivityCount === 0) {
            $score -= 10;
        }

        if ($avgUpdateDelayHours > 48) {
            $score -= 15;
        } elseif ($avgUpdateDelayHours > 24) {
            $score -= 8;
        }

        if ($followUpComplianceRate < 50) {
            $score -= 15;
        } elseif ($followUpComplianceRate < 75) {
            $score -= 8;
        }

        return max(0, min(100, $score));
    }

    private function healthState(int $score): string
    {
        if ($score >= 80) {
            return 'healthy';
        }

        if ($score >= 60) {
            return 'warning';
        }

        return 'critical';
    }
}
