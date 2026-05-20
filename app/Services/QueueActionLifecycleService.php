<?php

namespace App\Services;

use App\Models\LeadOperationalSnapshot;
use App\Models\LeadQueueActionHistory;
use App\Models\LeadQueueState;
use App\Models\Prospect;
use App\Models\User;
use Carbon\Carbon;

class QueueActionLifecycleService
{
    public const ACTION_DONE = 'done';
    public const ACTION_SNOOZE = 'snooze';
    public const ACTION_DISMISS = 'dismiss';

    public function __construct(
        private readonly LeadTimelineService $timeline
    ) {
    }

    public function markDone(Prospect $lead, User $actor, string $reasonTag, ?string $reasonNote = null): LeadQueueState
    {
        $snapshot = $this->requireSnapshot($lead->id);
        $fingerprint = $this->fingerprint($snapshot);

        $state = LeadQueueState::query()->updateOrCreate(
            ['lead_id' => $lead->id],
            [
                'action_fingerprint' => $fingerprint,
                'state' => self::ACTION_DONE,
                'snoozed_until' => null,
                'dismissed_until' => null,
                'reason_tag' => $reasonTag,
                'reason_note' => $reasonNote,
                'acted_by_user_id' => $actor->id,
                'acted_at' => now(),
            ]
        );

        $this->logAction($lead, $snapshot, $actor, self::ACTION_DONE, $reasonTag, $reasonNote, [
            'fingerprint' => $fingerprint,
        ]);

        return $state;
    }

    public function snooze(
        Prospect $lead,
        User $actor,
        string $duration,
        string $reasonTag,
        ?string $reasonNote = null
    ): LeadQueueState {
        $snapshot = $this->requireSnapshot($lead->id);
        $fingerprint = $this->fingerprint($snapshot);
        $snoozedUntil = $this->resolveSnoozeUntil($duration);

        $state = LeadQueueState::query()->updateOrCreate(
            ['lead_id' => $lead->id],
            [
                'action_fingerprint' => $fingerprint,
                'state' => self::ACTION_SNOOZE,
                'snoozed_until' => $snoozedUntil,
                'dismissed_until' => null,
                'reason_tag' => $reasonTag,
                'reason_note' => $reasonNote,
                'acted_by_user_id' => $actor->id,
                'acted_at' => now(),
            ]
        );

        $this->logAction($lead, $snapshot, $actor, self::ACTION_SNOOZE, $reasonTag, $reasonNote, [
            'duration' => $duration,
            'snoozed_until' => $snoozedUntil->toISOString(),
            'fingerprint' => $fingerprint,
        ]);

        return $state;
    }

    public function dismiss(Prospect $lead, User $actor, string $reasonTag, ?string $reasonNote = null): LeadQueueState
    {
        $snapshot = $this->requireSnapshot($lead->id);
        $fingerprint = $this->fingerprint($snapshot);
        $dismissUntil = now()->addMinutes(max(10, (int) config('scc.queue.dismiss_minutes', 480)));

        $state = LeadQueueState::query()->updateOrCreate(
            ['lead_id' => $lead->id],
            [
                'action_fingerprint' => $fingerprint,
                'state' => self::ACTION_DISMISS,
                'snoozed_until' => null,
                'dismissed_until' => $dismissUntil,
                'reason_tag' => $reasonTag,
                'reason_note' => $reasonNote,
                'acted_by_user_id' => $actor->id,
                'acted_at' => now(),
            ]
        );

        $this->logAction($lead, $snapshot, $actor, self::ACTION_DISMISS, $reasonTag, $reasonNote, [
            'dismissed_until' => $dismissUntil->toISOString(),
            'fingerprint' => $fingerprint,
        ]);

        return $state;
    }

    private function requireSnapshot(int $leadId): LeadOperationalSnapshot
    {
        return LeadOperationalSnapshot::query()
            ->where('lead_id', $leadId)
            ->firstOrFail();
    }

    private function resolveSnoozeUntil(string $duration): Carbon
    {
        $raw = match ($duration) {
            '30m' => now()->addMinutes(30),
            '2h' => now()->addHours(2),
            'tomorrow' => now()->addDay()->startOfDay()->addHours(9),
            default => now()->addMinutes(30),
        };

        $maxMinutes = max(30, (int) config('scc.queue.max_snooze_minutes', 1440));
        $maxUntil = now()->addMinutes($maxMinutes);
        if ($raw->greaterThan($maxUntil)) {
            return $maxUntil;
        }

        return $raw;
    }

    private function fingerprint(LeadOperationalSnapshot $snapshot): string
    {
        return implode(':', [
            $snapshot->id,
            $snapshot->version,
            $snapshot->next_action_code ?? 'none',
            $snapshot->priority_score,
        ]);
    }

    private function logAction(
        Prospect $lead,
        LeadOperationalSnapshot $snapshot,
        User $actor,
        string $actionType,
        string $reasonTag,
        ?string $reasonNote,
        array $payload = []
    ): void {
        LeadQueueActionHistory::query()->create([
            'lead_id' => $lead->id,
            'snapshot_id' => $snapshot->id,
            'action_fingerprint' => $this->fingerprint($snapshot),
            'action_type' => $actionType,
            'reason_tag' => $reasonTag,
            'reason_note' => $reasonNote,
            'payload' => $payload,
            'acted_by_user_id' => $actor->id,
            'acted_at' => now(),
        ]);

        $this->timeline->record(
            leadId: $lead->id,
            eventType: 'queue.action_'.$actionType,
            payload: [
                'reason_tag' => $reasonTag,
                'reason_note' => $reasonNote,
                ...$payload,
            ],
            actorType: 'user',
            actorId: $actor->id,
            source: 'crm',
            refType: 'lead_operational_snapshot',
            refId: $snapshot->id
        );
    }
}

