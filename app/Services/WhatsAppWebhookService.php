<?php

namespace App\Services;

use App\Models\Prospect;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\ClaraIntegrationService;
use App\Services\LeadTimelineService;
use Carbon\Carbon;

class WhatsAppWebhookService
{
    public function __construct(
        private readonly LeadTimelineService $timeline,
        private readonly LeadOperationalSnapshotService $snapshotService,
        private readonly ClaraIntegrationService $clara
    ) {
    }

    public function handle(array $payload): void
    {
        $entries = $payload['entry'] ?? [];

        if (! is_array($entries)) {
            return;
        }

        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $changes = $entry['changes'] ?? [];
            if (! is_array($changes)) {
                continue;
            }

            foreach ($changes as $change) {
                if (! is_array($change)) {
                    continue;
                }

                $value = $change['value'] ?? [];
                if (! is_array($value)) {
                    continue;
                }

                $this->storeInboundMessages($value);
                $this->storeStatuses($value);
            }
        }
    }

    private function storeInboundMessages(array $value): void
    {
        $businessNumber = $this->normalizePhone((string) data_get($value, 'metadata.display_phone_number', ''));
        $contactsByWaId = $this->buildContactsIndex($value['contacts'] ?? []);
        $messages = $value['messages'] ?? [];

        if (! is_array($messages)) {
            return;
        }

        foreach ($messages as $messagePayload) {
            if (! is_array($messagePayload)) {
                continue;
            }

            $fromNumber = $this->normalizePhone((string) ($messagePayload['from'] ?? ''));
            if ($fromNumber === '') {
                continue;
            }

            $contactName = data_get($contactsByWaId, $fromNumber.'.profile.name');
            $conversation = $this->resolveConversation($fromNumber, $contactName);
            $messageId = (string) ($messagePayload['id'] ?? '');
            $sentAt = $this->parseTimestamp($messagePayload['timestamp'] ?? null);
            $body = $this->extractBody($messagePayload);
            $messageType = (string) ($messagePayload['type'] ?? 'text');

            $attributes = [
                'conversation_id' => $conversation->id,
                'prospect_id' => $conversation->prospect_id,
                'direction' => WhatsAppMessage::DIRECTION_INBOUND,
                'message_type' => $messageType,
                'from_number' => $fromNumber,
                'to_number' => $businessNumber ?: null,
                'body' => $body,
                'status' => 'received',
                'sent_at' => $sentAt,
                'raw_payload' => $messagePayload,
            ];

            if ($messageId !== '') {
                $attributes['wa_message_id'] = $messageId;
                $savedMessage = WhatsAppMessage::query()->updateOrCreate(
                    ['wa_message_id' => $messageId],
                    $attributes
                );
            } else {
                $savedMessage = WhatsAppMessage::query()->create($attributes);
            }

            $conversation->forceFill([
                'last_message_at' => $this->maxTimestamp($conversation->last_message_at, $sentAt) ?? now(),
                'last_inbound_at' => $this->maxTimestamp($conversation->last_inbound_at, $sentAt) ?? now(),
                'unread_for_owner' => $conversation->unread_for_owner + 1,
            ])->save();

            if ($conversation->prospect_id) {
                $lead = Prospect::query()->find($conversation->prospect_id);
                if ($lead) {
                    $this->snapshotService->recomputeLead($lead);
                    $this->clara->enqueueLeadAnalysis($lead, 'message_received');
                }

                $this->timeline->record(
                    leadId: $conversation->prospect_id,
                    eventType: 'whatsapp.message_received',
                    payload: [
                        'conversation_id' => $conversation->id,
                        'wa_message_id' => $savedMessage->wa_message_id,
                        'from' => $fromNumber,
                        'message_type' => $messageType,
                        'excerpt' => mb_substr((string) ($body ?? ''), 0, 280),
                    ],
                    actorType: 'integration',
                    actorId: null,
                    source: 'wa_webhook',
                    refType: 'whatsapp_message',
                    refId: $savedMessage->id,
                    dedupeKey: $savedMessage->wa_message_id ? 'wa_inbound_'.$savedMessage->wa_message_id : null,
                    eventAt: $sentAt
                );
            }
        }
    }

    private function storeStatuses(array $value): void
    {
        $statuses = $value['statuses'] ?? [];
        if (! is_array($statuses)) {
            return;
        }

        foreach ($statuses as $statusPayload) {
            if (! is_array($statusPayload)) {
                continue;
            }

            $messageId = (string) ($statusPayload['id'] ?? '');
            if ($messageId === '') {
                continue;
            }

            $message = WhatsAppMessage::query()->where('wa_message_id', $messageId)->first();
            if (! $message) {
                continue;
            }

            $status = (string) ($statusPayload['status'] ?? '');
            $statusAt = $this->parseTimestamp($statusPayload['timestamp'] ?? null);

            $updates = [
                'status' => $status ?: $message->status,
                'raw_payload' => $statusPayload,
            ];

            if ($status === 'sent') {
                $updates['sent_at'] = $statusAt ?? $message->sent_at;
            }

            if ($status === 'delivered') {
                $updates['delivered_at'] = $statusAt ?? $message->delivered_at;
            }

            if ($status === 'read') {
                $updates['read_at'] = $statusAt ?? $message->read_at;
            }

            if ($status === 'failed') {
                $updates['failed_at'] = $statusAt ?? $message->failed_at;
            }

            $message->fill($updates)->save();

            $conversation = $message->conversation;
            if (! $conversation) {
                continue;
            }

            $nextLastMessageAt = $this->maxTimestamp($conversation->last_message_at, $statusAt);
            if ($nextLastMessageAt) {
                $conversation->forceFill([
                    'last_message_at' => $nextLastMessageAt,
                ])->save();
            }

            if ($conversation->prospect_id && $status !== '') {
                $lead = Prospect::query()->find($conversation->prospect_id);
                if ($lead) {
                    $this->snapshotService->recomputeLead($lead);
                }

                $this->timeline->record(
                    leadId: $conversation->prospect_id,
                    eventType: 'whatsapp.message_status_updated',
                    payload: [
                        'conversation_id' => $conversation->id,
                        'wa_message_id' => $message->wa_message_id,
                        'status' => $status,
                    ],
                    actorType: 'integration',
                    actorId: null,
                    source: 'wa_webhook',
                    refType: 'whatsapp_message',
                    refId: $message->id,
                    dedupeKey: $message->wa_message_id ? 'wa_status_'.$message->wa_message_id.'_'.$status : null,
                    eventAt: $statusAt
                );
            }
        }
    }

    private function resolveConversation(string $fromNumber, ?string $contactName): WhatsAppConversation
    {
        $conversation = WhatsAppConversation::query()->firstOrNew([
            'wa_chat_id' => $fromNumber,
        ]);

        if (! $conversation->prospect_phone) {
            $conversation->prospect_phone = $fromNumber;
        }

        if (! $conversation->contact_name && filled($contactName)) {
            $conversation->contact_name = $contactName;
        }

        if (! $conversation->prospect_id) {
            $prospect = $this->findProspectByPhone($fromNumber);
            if ($prospect) {
                $conversation->prospect_id = $prospect->id;
                $conversation->owner_id = $prospect->owner_id;
            }
        }

        $conversation->save();

        return $conversation;
    }

    private function findProspectByPhone(string $phone): ?Prospect
    {
        $variants = $this->phoneVariants($phone);
        if ($variants === []) {
            return null;
        }

        return Prospect::query()
            ->whereNotNull('phone')
            ->where(function ($query) use ($variants) {
                foreach ($variants as $variant) {
                    $query->orWhere('phone', $variant)
                        ->orWhere('phone', '+'.$variant)
                        ->orWhere('phone', 'like', '%'.$variant);
                }
            })
            ->orderByDesc('updated_at')
            ->first();
    }

    private function buildContactsIndex(array $contacts): array
    {
        return collect($contacts)
            ->filter(fn ($item) => is_array($item))
            ->mapWithKeys(function (array $item) {
                $waId = $this->normalizePhone((string) ($item['wa_id'] ?? ''));
                if ($waId === '') {
                    return [];
                }

                return [$waId => $item];
            })
            ->all();
    }

    private function phoneVariants(string $phone): array
    {
        $digits = $this->normalizePhone($phone);
        if ($digits === '') {
            return [];
        }

        $variants = [$digits];

        if (str_starts_with($digits, '0')) {
            $variants[] = '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '62')) {
            $variants[] = '0'.substr($digits, 2);
        }

        if (str_starts_with($digits, '8')) {
            $variants[] = '0'.$digits;
            $variants[] = '62'.$digits;
        }

        return array_values(array_unique(array_filter($variants)));
    }

    private function normalizePhone(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    private function extractBody(array $messagePayload): ?string
    {
        $type = (string) ($messagePayload['type'] ?? 'text');

        if ($type === 'text') {
            return data_get($messagePayload, 'text.body');
        }

        if ($type === 'button') {
            return data_get($messagePayload, 'button.text');
        }

        if ($type === 'interactive') {
            $replyTitle = data_get($messagePayload, 'interactive.button_reply.title');
            $listTitle = data_get($messagePayload, 'interactive.list_reply.title');
            $listDescription = data_get($messagePayload, 'interactive.list_reply.description');

            return $replyTitle ?: trim(implode(' - ', array_filter([$listTitle, $listDescription])));
        }

        if (in_array($type, ['image', 'video', 'document'], true)) {
            return data_get($messagePayload, $type.'.caption') ?: strtoupper($type).' message';
        }

        if ($type === 'audio') {
            return 'AUDIO message';
        }

        if ($type === 'sticker') {
            return 'STICKER message';
        }

        return strtoupper($type).' message';
    }

    private function parseTimestamp(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestamp((int) $value);
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function maxTimestamp(?Carbon $left, ?Carbon $right): ?Carbon
    {
        if (! $left) {
            return $right;
        }

        if (! $right) {
            return $left;
        }

        return $left->greaterThan($right) ? $left : $right;
    }
}
