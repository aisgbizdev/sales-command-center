<?php

namespace App\Http\Controllers;

use App\Services\ClaraIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClaraCallbackController extends Controller
{
    public function __invoke(Request $request, ClaraIntegrationService $clara): JsonResponse
    {
        $token = (string) config('clara.callback_token');
        $incoming = (string) $request->header('X-Clara-Token', '');

        if ($token === '' || ! hash_equals($token, $incoming)) {
            return response()->json(['accepted' => false, 'error' => 'invalid token'], 401);
        }

        $validated = $request->validate([
            'analysis_id' => ['required', 'string'],
            'lead_id' => ['required', 'integer'],
            'request_id' => ['nullable', 'string'],
            'generated_at' => ['nullable'],
            'confidence' => ['nullable', 'numeric'],
            'signals' => ['nullable', 'array'],
            'recommendation' => ['nullable', 'array'],
            'insight_version' => ['nullable'],
        ]);

        $insight = $clara->handleCallback($validated);

        Log::info('clara_callback_received', [
            'analysis_id' => $validated['analysis_id'],
            'lead_id' => $validated['lead_id'],
            'insight_id' => $insight->id,
        ]);

        return response()->json([
            'accepted' => true,
            'stored' => true,
        ]);
    }
}

