<?php

namespace App\Services;

use App\Models\LeadOperationalSnapshot;
use App\Models\Prospect;
use App\Models\WhatsAppMessage;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class LeadOperationalSnapshotService
{
    public function recomputeLead(Prospect $lead): LeadOperationalSnapshot
    {
        $messages = WhatsAppMessage::query()
            ->where('prospect_id', $lead->id)
            ->whereNotNull('sent_at')
            ->orderByDesc('sent_at')
            ->limit(100)
            ->get(['direction', 'sent_at']);

        $lastInboundAt = $messages
            ->firstWhere('direction', WhatsAppMessage::DIRECTION_INBOUND)
            ?->sent_at;
        $lastOutboundAt = $messages
            ->firstWhere('direction', WhatsAppMessage::DIRECTION_OUTBOUND)
            ?->sent_at;

        $outboundLast48h = $messages->filter(function (WhatsAppMessage $message): bool {
            return $message->direction === WhatsAppMessage::DIRECTION_OUTBOUND
                && $message->sent_at
                && $message->sent_at->gte(now()->subHours(48));
        })->count();

        $inboundLast48h = $messages->filter(function (WhatsAppMessage $message): bool {
            return $message->direction === WhatsAppMessage::DIRECTION_INBOUND
                && $message->sent_at
                && $message->sent_at->gte(now()->subHours(48));
        })->count();

        $overdueMinutes = $this->overdueMinutes($lead);
        $silenceMinutes = $this->silenceMinutes($lastInboundAt, $lastOutboundAt);
        $responseDelayMinutes = $this->responseDelayMinutes($lastInboundAt, $lastOutboundAt);
        $ownerOpenTasks = $this->ownerOpenTasks($lead);
        $ghostRiskScore = $this->ghostRiskScore($outboundLast48h, $inboundLast48h, $silenceMinutes);
        $temperatureScore = $this->temperatureScore($lead->user_temperature);

        $priorityScore = $this->priorityScore(
            stageWeight: $this->stageWeight($lead->status),
            overdueWeight: min(20, (int) floor($overdueMinutes / 60)),
            silenceWeight: min(15, (int) floor($silenceMinutes / 120)),
            leadScoreWeight: min(15, $lead->priority * 5),
            temperatureWeight: $temperatureScore >= 90 ? 10 : ($temperatureScore >= 60 ? 6 : 2),
            ghostWeight: min(10, (int) floor($ghostRiskScore / 10)),
            ownerLoadPenalty: min(12, (int) floor($ownerOpenTasks / 5))
        );

        [$nextActionCode, $nextActionConfidence, $nextActionExpiresAt] = $this->nextAction(
            lead: $lead,
            overdueMinutes: $overdueMinutes,
            ghostRiskScore: $ghostRiskScore,
            temperatureScore: $temperatureScore,
            silenceMinutes: $silenceMinutes
        );

        $snapshot = LeadOperationalSnapshot::query()->firstOrNew([
            'lead_id' => $lead->id,
        ]);

        $snapshot->fill([
            'owner_user_id' => $lead->owner_id,
            'pipeline_stage' => $lead->status,
            'priority_score' => $priorityScore,
            'priority_band' => $this->priorityBand($priorityScore),
            'ghost_risk_score' => $ghostRiskScore,
            'temperature_score' => $temperatureScore,
            'overdue_minutes' => $overdueMinutes,
            'response_delay_minutes' => $responseDelayMinutes,
            'last_inbound_at' => $lastInboundAt,
            'last_outbound_at' => $lastOutboundAt,
            'last_contacted_at' => $lead->last_contact_at,
            'owner_open_tasks' => $ownerOpenTasks,
            'next_action_code' => $nextActionCode,
            'next_action_confidence' => $nextActionConfidence,
            'next_action_expires_at' => $nextActionExpiresAt,
            'stale_after_at' => now()->addMinutes(15),
            'computed_at' => now(),
            'version' => $snapshot->exists ? ((int) $snapshot->version + 1) : 1,
        ]);
        $snapshot->save();

        return $snapshot->fresh();
    }

    /**
     * @param EloquentCollection<int, Prospect> $leads
     */
    public function recomputeCollection(EloquentCollection $leads): void
    {
        foreach ($leads as $lead) {
            $this->recomputeLead($lead);
        }
    }

    private function stageWeight(string $status): int
    {
        return match ($status) {
            Prospect::STATUS_PENUTUPAN => 25,
            Prospect::STATUS_TINDAK_LANJUT => 20,
            Prospect::STATUS_SEDANG_BERJALAN => 15,
            Prospect::STATUS_DIBALAS => 10,
            Prospect::STATUS_DIHUBUNGI => 7,
            Prospect::STATUS_BARU => 5,
            default => 0,
        };
    }

    private function temperatureScore(?string $temperature): int
    {
        return match ($temperature) {
            'hot' => 90,
            'warm' => 65,
            'cold' => 35,
            default => 40,
        };
    }

    private function overdueMinutes(Prospect $lead): int
    {
        if (! $lead->next_follow_up_date) {
            return 0;
        }

        $followUpAt = Carbon::parse($lead->next_follow_up_date)->endOfDay();
        if ($followUpAt->gte(now())) {
            return 0;
        }

        return (int) $followUpAt->diffInMinutes(now());
    }

    private function silenceMinutes(?Carbon $lastInboundAt, ?Carbon $lastOutboundAt): int
    {
        $lastSignal = collect([$lastInboundAt, $lastOutboundAt])
            ->filter()
            ->sortDesc()
            ->first();

        if (! $lastSignal) {
            return 24 * 60;
        }

        return (int) $lastSignal->diffInMinutes(now());
    }

    private function responseDelayMinutes(?Carbon $lastInboundAt, ?Carbon $lastOutboundAt): int
    {
        if (! $lastInboundAt || ! $lastOutboundAt) {
            return 0;
        }

        if ($lastOutboundAt->lte($lastInboundAt)) {
            return (int) $lastInboundAt->diffInMinutes(now());
        }

        return (int) $lastInboundAt->diffInMinutes($lastOutboundAt);
    }

    private function ownerOpenTasks(Prospect $lead): int
    {
        if (! $lead->owner_id) {
            return 0;
        }

        return Prospect::query()
            ->where('owner_id', $lead->owner_id)
            ->whereNotIn('status', [Prospect::STATUS_HILANG, Prospect::STATUS_PENUTUPAN])
            ->count();
    }

    private function ghostRiskScore(int $outboundLast48h, int $inboundLast48h, int $silenceMinutes): int
    {
        if ($outboundLast48h === 0) {
            return 0;
        }

        if ($inboundLast48h > 0) {
            return min(45, $outboundLast48h * 8);
        }

        return min(100, 45 + ($outboundLast48h * 10) + (int) floor($silenceMinutes / 180));
    }

    private function priorityScore(
        int $stageWeight,
        int $overdueWeight,
        int $silenceWeight,
        int $leadScoreWeight,
        int $temperatureWeight,
        int $ghostWeight,
        int $ownerLoadPenalty
    ): int {
        $score = $stageWeight
            + $overdueWeight
            + $silenceWeight
            + $leadScoreWeight
            + $temperatureWeight
            + $ghostWeight
            - $ownerLoadPenalty;

        return max(0, min(100, $score));
    }

    private function priorityBand(int $score): string
    {
        if ($score >= 75) {
            return 'p0';
        }

        if ($score >= 55) {
            return 'p1';
        }

        if ($score >= 35) {
            return 'p2';
        }

        return 'p3';
    }

    /**
     * @return array{0:string,1:float,2:Carbon}
     */
    private function nextAction(
        Prospect $lead,
        int $overdueMinutes,
        int $ghostRiskScore,
        int $temperatureScore,
        int $silenceMinutes
    ): array {
        if ($overdueMinutes > 0) {
            return ['followup_overdue_now', 88.0, now()->addHours(2)];
        }

        if ($ghostRiskScore >= 70) {
            return ['send_social_proof', 81.0, now()->addHours(3)];
        }

        if ($temperatureScore >= 85 && in_array($lead->status, [Prospect::STATUS_TINDAK_LANJUT, Prospect::STATUS_PENUTUPAN], true)) {
            return ['schedule_closing_call', 86.0, now()->addHours(2)];
        }

        if ($silenceMinutes >= 8 * 60) {
            return ['nudge_followup_message', 74.0, now()->addHours(6)];
        }

        return ['qualify_next_step', 64.0, now()->addHours(8)];
    }
}

