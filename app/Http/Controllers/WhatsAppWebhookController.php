<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppWebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        private readonly WhatsAppWebhookService $service
    ) {
    }

    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $verifyToken = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge', ''));

        if (
            $mode === 'subscribe' &&
            $verifyToken !== '' &&
            hash_equals((string) config('services.whatsapp.verify_token'), $verifyToken)
        ) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    public function receive(Request $request)
    {
        if (! $this->hasValidSignature($request)) {
            return response()->json([
                'message' => 'Invalid signature.',
            ], 401);
        }

        try {
            $this->service->handle($request->all());
        } catch (\Throwable $e) {
            Log::error('WhatsApp webhook processing failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Webhook processing failed.',
            ], 500);
        }

        return response()->json([
            'message' => 'EVENT_RECEIVED',
        ]);
    }

    private function hasValidSignature(Request $request): bool
    {
        $appSecret = (string) config('services.whatsapp.app_secret');
        if ($appSecret === '') {
            return true;
        }

        $signature = (string) $request->header('X-Hub-Signature-256', '');
        if ($signature === '') {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expected, $signature);
    }
}
