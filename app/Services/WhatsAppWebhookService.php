<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageEvent;
use App\Models\Prospect;
use Carbon\Carbon;

class WhatsAppWebhookService
{
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
        $businessPhone = $this->normalizePhone((string) data_get($value, 'metadata.display_phone_number', ''));
        $contactsByWaId = $this->buildContactsIndex($value['contacts'] ?? []);
        $messages = $value['messages'] ?? [];

        if (! is_array($messages)) {
            return;
        }

        foreach ($messages as $payload) {
            if (! is_array($payload)) {
                continue;
            }

            $senderPhone = $this->normalizePhone((string) ($payload['from'] ?? ''));
            if ($senderPhone === '') {
                continue;
            }

            $prospect = $this->findProspectByPhone($senderPhone);
            if (! $prospect) {
                continue;
            }

            $conversation = Conversation::query()->firstOrCreate(
                [
                    'prospect_id' => $prospect->id,
                    'channel' => 'whatsapp',
                ],
                [
                    'assigned_user_id' => $prospect->owner_id,
                    'status' => 'active',
                ]
            );

            $messageType = (string) ($payload['type'] ?? 'text');
            $content = $this->extractBody($payload);
            $mediaUrl = $this->extractMediaUrl($payload, $messageType);
            $waMessageId = (string) ($payload['id'] ?? '');
            $sentAt = $this->parseTimestamp($payload['timestamp'] ?? null) ?? now();

            $messageAttributes = [
                'conversation_id' => $conversation->id,
                'direction' => Message::DIRECTION_INCOMING,
                'message_type' => $messageType,
                'sender_phone' => $senderPhone,
                'receiver_phone' => $businessPhone !== '' ? $businessPhone : null,
                'content' => $content,
                'media_url' => $mediaUrl,
                'status' => 'received',
                'sent_at' => $sentAt,
            ];

            $message = $waMessageId !== ''
                ? Message::query()->updateOrCreate(['wa_message_id' => $waMessageId], $messageAttributes)
                : Message::query()->create($messageAttributes);

            MessageEvent::query()->create([
                'message_id' => $message->id,
                'event_type' => 'incoming',
                'payload' => $payload,
            ]);

            $conversation->forceFill([
                'last_message_at' => $this->maxTimestamp($conversation->last_message_at, $sentAt) ?? now(),
                'last_message_preview' => $this->preview($content, $messageType),
                'unread_count' => $conversation->unread_count + 1,
                'assigned_user_id' => $conversation->assigned_user_id ?: $prospect->owner_id,
            ])->save();

            $prospect->updateQuietly([
                'last_activity_at' => now(),
                'last_contact_at' => $sentAt,
            ]);

            if (($contactsByWaId[$senderPhone]['profile']['name'] ?? null) && ! $prospect->company) {
                $prospect->updateQuietly(['company' => $contactsByWaId[$senderPhone]['profile']['name']]);
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

            $waMessageId = (string) ($statusPayload['id'] ?? '');
            if ($waMessageId === '') {
                continue;
            }

            $message = Message::query()->where('wa_message_id', $waMessageId)->first();
            if (! $message) {
                continue;
            }

            $status = (string) ($statusPayload['status'] ?? '');
            $eventAt = $this->parseTimestamp($statusPayload['timestamp'] ?? null);

            $updates = [
                'status' => $status !== '' ? $status : $message->status,
            ];

            if ($status === 'sent') {
                $updates['sent_at'] = $eventAt ?? $message->sent_at;
            }

            if ($status === 'delivered') {
                $updates['delivered_at'] = $eventAt ?? $message->delivered_at;
            }

            if ($status === 'read') {
                $updates['read_at'] = $eventAt ?? $message->read_at;
            }

            $message->fill($updates)->save();

            MessageEvent::query()->create([
                'message_id' => $message->id,
                'event_type' => $status !== '' ? $status : 'status_update',
                'payload' => $statusPayload,
            ]);

            $conversation = $message->conversation;
            if ($conversation && $eventAt) {
                $conversation->forceFill([
                    'last_message_at' => $this->maxTimestamp($conversation->last_message_at, $eventAt),
                ])->save();
            }
        }
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

                return $waId !== '' ? [$waId => $item] : [];
            })
            ->all();
    }

    private function extractBody(array $payload): ?string
    {
        $type = (string) ($payload['type'] ?? 'text');

        return match ($type) {
            'text' => data_get($payload, 'text.body'),
            'button' => data_get($payload, 'button.text'),
            'interactive' => data_get($payload, 'interactive.button_reply.title')
                ?: trim(implode(' - ', array_filter([
                    data_get($payload, 'interactive.list_reply.title'),
                    data_get($payload, 'interactive.list_reply.description'),
                ]))),
            'image', 'video', 'document' => data_get($payload, $type.'.caption') ?: strtoupper($type).' message',
            'audio' => 'AUDIO message',
            'sticker' => 'STICKER message',
            default => strtoupper($type).' message',
        };
    }

    private function extractMediaUrl(array $payload, string $type): ?string
    {
        if (! in_array($type, ['image', 'video', 'document', 'audio', 'sticker'], true)) {
            return null;
        }

        return data_get($payload, $type.'.id') ?: data_get($payload, $type.'.link');
    }

    private function preview(?string $content, string $type): string
    {
        $text = trim((string) $content);

        if ($text === '') {
            $text = strtoupper($type).' message';
        }

        return mb_substr($text, 0, 255);
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

    private function normalizePhone(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
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
}
