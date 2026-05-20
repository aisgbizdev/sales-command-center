<?php

namespace App\Services;

use App\Jobs\SendClaraAnalysisJob;
use App\Models\AiRequest;
use App\Models\LeadAiInsight;
use App\Models\LeadOperationalSnapshot;
use App\Models\Prospect;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ClaraIntegrationService
{
    public function enqueueLeadAnalysis(Prospect $lead, string $triggerType): ?AiRequest
    {
        if (! $this->canTrigger($lead->id, $triggerType)) {
            return null;
        }

        $request = AiRequest::query()->create([
            'request_id' => (string) Str::uuid(),
            'lead_id' => $lead->id,
            'conversation_id' => null,
            'trigger_type' => $triggerType,
            'status' => 'pending',
            'attempt_count' => 0,
            'payload_hash' => null,
            'last_error' => null,
            'sent_at' => null,
            'completed_at' => null,
        ]);

        SendClaraAnalysisJob::dispatch($request->id);

        return $request;
    }

    public function dispatchRequest(AiRequest $request): void
    {
        $lead = Prospect::query()->with('operationalSnapshot')->find($request->lead_id);
        if (! $lead) {
            $request->update(['status' => 'failed', 'last_error' => 'Lead not found']);
            return;
        }

        $baseUrl = rtrim((string) config('clara.base_url'), '/');
        if ($baseUrl === '') {
            $request->update(['status' => 'failed', 'last_error' => 'CLARA_BASE_URL not configured']);
            return;
        }

        [$endpoint, $payload] = $this->buildPayload($request, $lead);
        $payloadHash = hash('sha256', json_encode($payload));

        try {
            $response = Http::timeout((int) config('clara.timeout_seconds', 20))
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer '.(string) config('clara.api_key'),
                ])
                ->post($baseUrl.$endpoint, $payload);

            $request->update([
                'status' => $response->successful() ? 'sent' : 'failed',
                'attempt_count' => $request->attempt_count + 1,
                'payload_hash' => $payloadHash,
                'last_error' => $response->successful() ? null : $response->body(),
                'sent_at' => now(),
            ]);

            Log::channel('stack')->info('clara_request_sent', [
                'request_id' => $request->request_id,
                'lead_id' => $request->lead_id,
                'trigger_type' => $request->trigger_type,
                'endpoint' => $endpoint,
                'status_code' => $response->status(),
            ]);
        } catch (\Throwable $e) {
            $request->update([
                'status' => 'failed',
                'attempt_count' => $request->attempt_count + 1,
                'payload_hash' => $payloadHash,
                'last_error' => $e->getMessage(),
                'sent_at' => now(),
            ]);
        }
    }

    public function handleCallback(array $payload): LeadAiInsight
    {
        $leadId = (int) ($payload['lead_id'] ?? 0);
        $requestId = (string) ($payload['request_id'] ?? '');
        $signals = (array) ($payload['signals'] ?? []);
        $recommendation = (array) ($payload['recommendation'] ?? []);

        $attributes = [
            'lead_id' => $leadId,
            'request_id' => $requestId !== '' ? $requestId : null,
            'lead_score' => isset($signals['lead_score_ai']) ? (int) $signals['lead_score_ai'] : null,
            'temperature' => $signals['temperature_ai'] ?? null,
            'dominant_emotion' => $signals['emotion_ai'] ?? null,
            'top_objections' => isset($signals['objection_top']) ? [$signals['objection_top']] : null,
            'next_best_actions' => isset($recommendation['code']) ? [[
                'code' => $recommendation['code'],
                'text' => $recommendation['text'] ?? null,
            ]] : null,
            'bridge_recommendation' => null,
            'risk_flags' => [
                'ghost_risk_ai' => $signals['ghost_risk_ai'] ?? null,
            ],
            'insight_version' => (string) ($payload['insight_version'] ?? '1'),
            'confidence' => isset($payload['confidence']) ? (float) $payload['confidence'] : null,
            'generated_at' => $payload['generated_at'] ?? now(),
            'expires_at' => $recommendation['expires_at'] ?? now()->addHours(6),
        ];

        $insight = $requestId !== ''
            ? LeadAiInsight::query()->updateOrCreate(['request_id' => $requestId], $attributes)
            : LeadAiInsight::query()->create($attributes);

        if ($requestId !== '') {
            AiRequest::query()
                ->where('request_id', $requestId)
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'last_error' => null,
                ]);
        }

        return $insight;
    }

    private function buildPayload(AiRequest $request, Prospect $lead): array
    {
        $snapshot = $lead->operationalSnapshot;
        $common = [
            'request_id' => $request->request_id,
            'event_id' => 'evt_'.Str::uuid(),
            'occurred_at' => now()->toIso8601String(),
            'lead' => [
                'id' => $lead->id,
                'code' => $lead->prospect_code,
                'status' => $lead->status,
                'owner_id' => $lead->owner_id,
                'source' => $lead->source,
                'account_category' => $lead->account_category,
            ],
            'snapshot' => [
                'priority_score' => $snapshot?->priority_score,
                'overdue_minutes' => $snapshot?->overdue_minutes,
                'response_delay_minutes' => $snapshot?->response_delay_minutes,
            ],
        ];

        if ($request->trigger_type === 'message_received') {
            $messages = WhatsAppMessage::query()
                ->where('prospect_id', $lead->id)
                ->latest('sent_at')
                ->limit(20)
                ->get(['direction', 'body', 'sent_at'])
                ->map(fn (WhatsAppMessage $m) => [
                    'direction' => $m->direction,
                    'text' => $m->body,
                    'at' => $m->sent_at?->toIso8601String(),
                ])->values()->all();

            return ['/api/clara/analyze-chat', [
                ...$common,
                'lead_id' => $lead->id,
                'channel' => 'whatsapp',
                'messages' => $messages,
            ]];
        }

        if ($request->trigger_type === 'followup_overdue') {
            return ['/api/clara/analyze-followup', [
                ...$common,
                'lead_id' => $lead->id,
                'followup' => [
                    'state' => $lead->follow_up_state,
                    'scheduled_at' => $lead->next_follow_up_date?->toDateString(),
                    'owner_id' => $lead->owner_id,
                ],
            ]];
        }

        return ['/api/clara/analyze-lead', $common];
    }

    private function canTrigger(int $leadId, string $triggerType): bool
    {
        $cooldown = (int) data_get(config('clara.cooldowns'), $triggerType, 120);
        $cutoff = now()->subSeconds($cooldown);

        return ! AiRequest::query()
            ->where('lead_id', $leadId)
            ->where('trigger_type', $triggerType)
            ->where('created_at', '>=', $cutoff)
            ->exists();
    }
}
