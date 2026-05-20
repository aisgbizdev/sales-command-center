<?php

namespace App\Services;

use App\Models\LeadTimelineEvent;
use Carbon\CarbonInterface;

class LeadTimelineService
{
    public function record(
        int $leadId,
        string $eventType,
        array $payload = [],
        string $actorType = 'system',
        ?int $actorId = null,
        string $source = 'crm',
        ?string $refType = null,
        ?int $refId = null,
        ?string $dedupeKey = null,
        ?CarbonInterface $eventAt = null
    ): LeadTimelineEvent {
        if ($dedupeKey) {
            $existing = LeadTimelineEvent::query()->where('dedupe_key', $dedupeKey)->first();
            if ($existing) {
                return $existing;
            }
        }

        return LeadTimelineEvent::query()->create([
            'lead_id' => $leadId,
            'event_type' => $eventType,
            'event_at' => $eventAt ?? now(),
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'source' => $source,
            'ref_type' => $refType,
            'ref_id' => $refId,
            'payload' => $payload,
            'dedupe_key' => $dedupeKey,
        ]);
    }
}

