<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppWebhookEvent;
use App\Services\WhatsAppWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        private readonly WhatsAppWebhookService $webhookService
    ) {
    }

    public function handle(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if (
            $mode === 'subscribe'
            && $token === config('services.whatsapp.verify_token')
        ) {
            Log::info('WHATSAPP VERIFIED');

            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        $payload = $request->all();
        Log::info('WHATSAPP_WEBHOOK', $payload);

        WhatsAppWebhookEvent::query()->create([
            'payload' => $payload,
            'event_type' => data_get($payload, 'entry.0.changes.0.field'),
        ]);

        try {
            $this->webhookService->handle($payload);
        } catch (\Throwable $e) {
            Log::error('WHATSAPP_WEBHOOK_PROCESSING_FAILED', [
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
        ]);
    }
}
