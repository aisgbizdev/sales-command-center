<?php

namespace App\Services;

use App\Models\Prospect;
use App\Models\ProspectLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ObjectionAnalyticsService
{
    public function insights(Builder $prospectsQuery): array
    {
        $sources = $this->objectionSources($prospectsQuery);

        return [
            'topObjections' => $this->topObjections($sources),
            'objectionByCategory' => $this->objectionByCategory($sources),
            'objectionConversion' => $this->objectionConversion($sources),
            'objectionBySource' => $this->objectionBySource($sources),
            'highRiskObjections' => $this->highRiskObjections($sources),
            'managerInsights' => $this->managerInsights($sources),
        ];
    }

    private function objectionSources(Builder $prospectsQuery): Collection
    {
        $prospectIds = (clone $prospectsQuery)->select('prospects.id');

        $logRows = ProspectLog::query()
            ->join('prospects', 'prospects.id', '=', 'prospect_logs.prospect_id')
            ->whereIn('prospect_logs.prospect_id', $prospectIds)
            ->whereNotNull('prospect_logs.objection_type')
            ->select([
                'prospect_logs.objection_type',
                'prospect_logs.emotional_state',
                'prospects.id as prospect_id',
                'prospects.account_category',
                'prospects.source',
                'prospects.status',
                DB::raw("'log' as source_type"),
            ])
            ->get();

        $reviewRows = DB::table('chat_reviews')
            ->join('prospects', 'prospects.id', '=', 'chat_reviews.prospect_id')
            ->whereIn('chat_reviews.prospect_id', (clone $prospectsQuery)->select('prospects.id'))
            ->whereNotNull('chat_reviews.objection_type')
            ->select([
                'chat_reviews.objection_type',
                'chat_reviews.emotional_state',
                'prospects.id as prospect_id',
                'prospects.account_category',
                'prospects.source',
                'prospects.status',
                DB::raw("'chat_review' as source_type"),
            ])
            ->get();

        return $logRows->concat($reviewRows)
            ->filter(fn ($row) => filled($row->objection_type))
            ->values();
    }

    private function topObjections(Collection $sources): Collection
    {
        return $sources
            ->groupBy('objection_type')
            ->map(fn (Collection $items, string $type) => [
                'objection' => $type,
                'label' => $this->objectionLabel($type),
                'total' => $items->count(),
            ])
            ->sortByDesc('total')
            ->values();
    }

    private function objectionByCategory(Collection $sources): Collection
    {
        return $sources
            ->groupBy(fn ($row) => $row->account_category ?: 'unknown')
            ->map(fn (Collection $items, string $category) => [
                'category' => $category,
                'label' => Prospect::ACCOUNT_CATEGORY_LABELS[$category] ?? ucfirst($category),
                'items' => $this->topObjections($items)->take(8)->values(),
            ])
            ->values();
    }

    private function objectionConversion(Collection $sources): Collection
    {
        return $sources
            ->groupBy('objection_type')
            ->map(function (Collection $items, string $type) {
                $prospects = $items->unique('prospect_id');
                $total = $prospects->count();
                $closing = $prospects->where('status', Prospect::STATUS_PENUTUPAN)->count();

                return [
                    'objection' => $type,
                    'label' => $this->objectionLabel($type),
                    'total' => $total,
                    'closing' => $closing,
                    'conversionRate' => $total > 0 ? round(($closing / $total) * 100, 1) : 0,
                ];
            })
            ->sortByDesc('conversionRate')
            ->values();
    }

    private function objectionBySource(Collection $sources): Collection
    {
        return $sources
            ->groupBy(fn ($row) => $row->source ?: 'unknown')
            ->map(function (Collection $items, string $source) {
                $total = max(1, $items->count());

                return [
                    'source' => $source,
                    'label' => ucfirst(str_replace('_', ' ', $source)),
                    'items' => $items
                        ->groupBy('objection_type')
                        ->map(fn (Collection $group, string $type) => [
                            'objection' => $type,
                            'label' => $this->objectionLabel($type),
                            'total' => $group->count(),
                            'percent' => round(($group->count() / $total) * 100, 1),
                        ])
                        ->sortByDesc('total')
                        ->values(),
                ];
            })
            ->values();
    }

    private function highRiskObjections(Collection $sources): Collection
    {
        return $sources
            ->groupBy('objection_type')
            ->map(function (Collection $items, string $type) {
                $prospects = $items->unique('prospect_id');
                $total = $prospects->count();
                $closing = $prospects->where('status', Prospect::STATUS_PENUTUPAN)->count();
                $lost = $prospects->where('status', Prospect::STATUS_HILANG)->count();
                $conversionRate = $total > 0 ? round(($closing / $total) * 100, 1) : 0;
                $lostRate = $total > 0 ? round(($lost / $total) * 100, 1) : 0;

                return [
                    'objection' => $type,
                    'label' => $this->objectionLabel($type),
                    'total' => $total,
                    'conversionRate' => $conversionRate,
                    'lostRate' => $lostRate,
                    'riskScore' => round((100 - $conversionRate) + $lostRate, 1),
                ];
            })
            ->sortByDesc('riskScore')
            ->values();
    }

    private function managerInsights(Collection $sources): array
    {
        $top = $this->topObjections($sources)->first();
        $hardest = $this->highRiskObjections($sources)->first();
        $regular = $sources->where('account_category', 'reguler');
        $regularTop = $this->topObjections($regular)->first();

        return [
            'mostCommonThisWeek' => $top,
            'lowestConversion' => $hardest,
            'regularAccountTopObjection' => $regularTop,
        ];
    }

    private function objectionLabel(string $type): string
    {
        return ProspectLog::OBJECTION_TYPE_LABELS[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }
}
