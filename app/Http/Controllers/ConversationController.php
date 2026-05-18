<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Prospect;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConversationController extends Controller
{
    public function show(Request $request, Prospect $prospect): JsonResponse
    {
        $this->authorize('viewAny', Prospect::class);
        $user = $request->user();

        abort_unless($this->scopedProspects($user)->whereKey($prospect->id)->exists(), 404);

        $conversation = Conversation::query()
            ->where('prospect_id', $prospect->id)
            ->where('channel', 'whatsapp')
            ->with(['messages' => fn ($q) => $q->latest('id')->limit(100)])
            ->first();

        return response()->json([
            'conversation' => $conversation ? [
                'id' => $conversation->id,
                'channel' => $conversation->channel,
                'status' => $conversation->status,
                'unreadCount' => (int) $conversation->unread_count,
                'lastMessageAt' => $conversation->last_message_at?->toISOString(),
                'lastMessagePreview' => $conversation->last_message_preview,
                'assignedUserId' => $conversation->assigned_user_id ? (string) $conversation->assigned_user_id : null,
            ] : null,
            'messages' => $conversation
                ? $conversation->messages->sortBy('id')->values()->map(fn (Message $message) => [
                    'id' => $message->id,
                    'waMessageId' => $message->wa_message_id,
                    'direction' => $message->direction,
                    'messageType' => $message->message_type,
                    'senderPhone' => $message->sender_phone,
                    'receiverPhone' => $message->receiver_phone,
                    'content' => $message->content,
                    'mediaUrl' => $message->media_url,
                    'status' => $message->status,
                    'errorMessage' => $message->error_message,
                    'sentAt' => $message->sent_at?->toISOString() ?? $message->created_at?->toISOString(),
                    'deliveredAt' => $message->delivered_at?->toISOString(),
                    'readAt' => $message->read_at?->toISOString(),
                ])->all()
                : [],
            'prospect' => [
                'id' => $prospect->id,
                'name' => $prospect->name,
                'phone' => $prospect->phone,
            ],
        ]);
    }

    public function send(Request $request, Prospect $prospect, WhatsAppService $whatsAppService): JsonResponse
    {
        $this->authorize('viewAny', Prospect::class);
        $user = $request->user();

        abort_unless($this->scopedProspects($user)->whereKey($prospect->id)->exists(), 404);
        abort_unless($user->canEditProspect($prospect), 403);

        $validated = $request->validate([
            'type' => ['required', Rule::in(['text', 'image', 'template'])],
            'text' => ['nullable', 'string', 'max:4096'],
            'image_url' => ['nullable', 'url', 'max:1024'],
            'caption' => ['nullable', 'string', 'max:1000'],
            'template_name' => ['nullable', 'string', 'max:255'],
            'template_language' => ['nullable', 'string', 'max:20'],
            'template_components' => ['nullable', 'array'],
        ]);

        $type = $validated['type'];

        if ($type === 'text') {
            $text = trim((string) ($validated['text'] ?? ''));
            abort_if($text === '', 422, 'Text message is required.');
            $message = $whatsAppService->sendTextMessage($prospect, $text, $user->id);
        } elseif ($type === 'image') {
            $imageUrl = (string) ($validated['image_url'] ?? '');
            abort_if($imageUrl === '', 422, 'Image URL is required.');
            $message = $whatsAppService->sendImageMessage($prospect, $imageUrl, $validated['caption'] ?? null, $user->id);
        } else {
            $templateName = trim((string) ($validated['template_name'] ?? ''));
            abort_if($templateName === '', 422, 'Template name is required.');
            $message = $whatsAppService->sendTemplateMessage(
                $prospect,
                $templateName,
                (string) ($validated['template_language'] ?? 'id'),
                (array) ($validated['template_components'] ?? []),
                $user->id
            );
        }

        return response()->json([
            'message' => $message->status === 'failed' ? 'Pesan gagal dikirim ke Meta API.' : 'Pesan berhasil dikirim.',
            'item' => [
                'id' => $message->id,
                'direction' => $message->direction,
                'messageType' => $message->message_type,
                'content' => $message->content,
                'mediaUrl' => $message->media_url,
                'status' => $message->status,
                'errorMessage' => $message->error_message,
                'waMessageId' => $message->wa_message_id,
                'sentAt' => $message->sent_at?->toISOString() ?? $message->created_at?->toISOString(),
            ],
        ], $message->status === 'failed' ? 422 : 200);
    }

    private function scopedProspects(User $user): Builder
    {
        return Prospect::query()
            ->when($user->isPenjualan(), fn (Builder $q) => $q->where('owner_id', $user->id))
            ->when($user->isManager(), fn (Builder $q) => $q->where('team_id', $user->team_id))
            ->when($user->isKepala(), fn (Builder $q) => $q->where('unit_id', $user->unit_id));
    }
}
