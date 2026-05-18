<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageEvent;
use App\Models\Prospect;
use App\Models\ProspectLog;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WhatsAppService
{
    public function __construct(
        private readonly HttpFactory $http
    ) {
    }

    public function sendTextMessage(Prospect $prospect, string $text, ?int $senderUserId = null): Message
    {
        $to = $this->resolveRecipientPhone($prospect);

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $text,
            ],
        ];

        return $this->sendMessage($prospect, $payload, [
            'message_type' => 'text',
            'content' => $text,
            'media_url' => null,
        ], $senderUserId);
    }

    public function sendImageMessage(Prospect $prospect, string $imageUrl, ?string $caption = null, ?int $senderUserId = null): Message
    {
        $to = $this->resolveRecipientPhone($prospect);

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'image',
            'image' => array_filter([
                'link' => $imageUrl,
                'caption' => $caption,
            ]),
        ];

        return $this->sendMessage($prospect, $payload, [
            'message_type' => 'image',
            'content' => $caption ?: 'Image message',
            'media_url' => $imageUrl,
        ], $senderUserId);
    }

    public function sendTemplateMessage(Prospect $prospect, string $templateName, string $languageCode = 'id', array $components = [], ?int $senderUserId = null): Message
    {
        $to = $this->resolveRecipientPhone($prospect);

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => array_filter([
                'name' => $templateName,
                'language' => ['code' => $languageCode],
                'components' => $components !== [] ? $components : null,
            ]),
        ];

        return $this->sendMessage($prospect, $payload, [
            'message_type' => 'template',
            'content' => "Template: {$templateName}",
            'media_url' => null,
        ], $senderUserId);
    }

    private function sendMessage(Prospect $prospect, array $payload, array $meta, ?int $senderUserId): Message
    {
        $context = $this->buildApiContext();
        $conversation = $this->resolveConversation($prospect);
        $message = $this->createPendingMessage($conversation, $prospect, $meta, $context['business_phone']);

        Log::channel('whatsapp')->info('whatsapp_send_request', [
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
            'url' => $context['url'],
            'payload' => $payload,
        ]);

        try {
            $response = $this->http->withToken($context['token'])
                ->acceptJson()
                ->post($context['url'], $payload);

            $responseBody = $response->json();
            $responseArray = is_array($responseBody) ? $responseBody : ['raw' => $response->body()];

            Log::channel('whatsapp')->info('whatsapp_send_response', [
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'status_code' => $response->status(),
                'response' => $responseArray,
            ]);

            MessageEvent::query()->create([
                'message_id' => $message->id,
                'event_type' => 'outgoing_api_response',
                'payload' => $responseArray,
            ]);

            if ($response->failed()) {
                $errorMessage = (string) (Arr::get($responseArray, 'error.message') ?: 'Meta API request failed.');

                $message->forceFill([
                    'status' => 'failed',
                    'error_message' => $errorMessage,
                ])->save();

                $conversation->forceFill([
                    'last_message_at' => now(),
                    'last_message_preview' => mb_substr((string) ($meta['content'] ?? ''), 0, 255),
                ])->save();

                return $message->fresh();
            }

            $waMessageId = (string) Arr::get($responseArray, 'messages.0.id', '');

            $message->forceFill([
                'wa_message_id' => $waMessageId !== '' ? $waMessageId : null,
                'status' => 'sent',
                'error_message' => null,
                'sent_at' => now(),
            ])->save();

            $conversation->forceFill([
                'last_message_at' => now(),
                'last_message_preview' => mb_substr((string) ($meta['content'] ?? ''), 0, 255),
                'assigned_user_id' => $conversation->assigned_user_id ?: $prospect->owner_id,
                'status' => 'active',
            ])->save();

            ProspectLog::query()->create([
                'prospect_id' => $prospect->id,
                'user_id' => $senderUserId ?: $prospect->owner_id,
                'log_date' => now()->toDateString(),
                'activity_type' => 'follow_up',
                'summary' => 'Outgoing WhatsApp: '.mb_substr((string) ($meta['content'] ?? 'Message'), 0, 120),
                'result' => 'Pesan terkirim via CRM WhatsApp layer',
            ]);

            $prospect->updateQuietly([
                'last_activity_at' => now(),
                'last_contact_at' => now(),
            ]);

            return $message->fresh();
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('whatsapp_send_exception', [
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'message' => $e->getMessage(),
            ]);

            MessageEvent::query()->create([
                'message_id' => $message->id,
                'event_type' => 'outgoing_api_exception',
                'payload' => ['error' => $e->getMessage()],
            ]);

            $message->forceFill([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ])->save();

            return $message->fresh();
        }
    }

    private function resolveConversation(Prospect $prospect): Conversation
    {
        return Conversation::query()->firstOrCreate(
            [
                'prospect_id' => $prospect->id,
                'channel' => 'whatsapp',
            ],
            [
                'assigned_user_id' => $prospect->owner_id,
                'status' => 'active',
            ]
        );
    }

    private function createPendingMessage(Conversation $conversation, Prospect $prospect, array $meta, string $businessPhone): Message
    {
        return Message::query()->create([
            'conversation_id' => $conversation->id,
            'direction' => Message::DIRECTION_OUTGOING,
            'message_type' => (string) $meta['message_type'],
            'sender_phone' => $this->normalizePhone($businessPhone),
            'receiver_phone' => $this->normalizePhone((string) $prospect->phone),
            'content' => (string) ($meta['content'] ?? ''),
            'media_url' => $meta['media_url'] ?? null,
            'status' => 'pending',
            'sent_at' => null,
            'error_message' => null,
        ]);
    }

    private function buildApiContext(): array
    {
        $version = (string) config('services.whatsapp.api_version', 'v22.0');
        $phoneNumberId = (string) config('services.whatsapp.phone_number_id');
        $token = (string) (config('services.whatsapp.access_token') ?: config('services.whatsapp.token'));
        $baseUrl = rtrim((string) config('services.whatsapp.base_url', 'https://graph.facebook.com'), '/');
        $businessPhone = (string) config('services.whatsapp.business_phone', '');

        if ($phoneNumberId === '' || $token === '') {
            throw new RuntimeException('WhatsApp API credentials are missing.');
        }

        return [
            'url' => "{$baseUrl}/{$version}/{$phoneNumberId}/messages",
            'token' => $token,
            'business_phone' => $businessPhone,
        ];
    }

    private function resolveRecipientPhone(Prospect $prospect): string
    {
        $phone = $this->normalizePhone((string) $prospect->phone);

        if ($phone === '') {
            throw new RuntimeException('Prospect does not have a valid phone number.');
        }

        if (str_starts_with($phone, '0')) {
            return '62'.substr($phone, 1);
        }

        if (str_starts_with($phone, '8')) {
            return '62'.$phone;
        }

        return $phone;
    }

    private function normalizePhone(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }
}
