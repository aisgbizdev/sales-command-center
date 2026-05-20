<?php

namespace App\Http\Controllers;

use App\Models\Prospect;
use App\Services\ClaraIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClaraIntegrationController extends Controller
{
    public function analyzeLead(Request $request, Prospect $prospect, ClaraIntegrationService $clara): JsonResponse
    {
        $this->authorize('viewAny', Prospect::class);
        $user = $request->user();
        abort_unless($this->canAccessLead($user, $prospect), 404);

        $queued = $clara->enqueueLeadAnalysis($prospect, 'lead_created');

        return response()->json([
            'queued' => (bool) $queued,
            'request_id' => $queued?->request_id,
        ]);
    }

    public function analyzeChat(Request $request, Prospect $prospect, ClaraIntegrationService $clara): JsonResponse
    {
        $this->authorize('viewAny', Prospect::class);
        $user = $request->user();
        abort_unless($this->canAccessLead($user, $prospect), 404);

        $queued = $clara->enqueueLeadAnalysis($prospect, 'message_received');

        return response()->json([
            'queued' => (bool) $queued,
            'request_id' => $queued?->request_id,
        ]);
    }

    public function analyzeFollowup(Request $request, Prospect $prospect, ClaraIntegrationService $clara): JsonResponse
    {
        $this->authorize('viewAny', Prospect::class);
        $user = $request->user();
        abort_unless($this->canAccessLead($user, $prospect), 404);

        $queued = $clara->enqueueLeadAnalysis($prospect, 'followup_overdue');

        return response()->json([
            'queued' => (bool) $queued,
            'request_id' => $queued?->request_id,
        ]);
    }

    private function canAccessLead($user, Prospect $prospect): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isPenjualan()) {
            return $prospect->owner_id === $user->id;
        }

        if ($user->isManager()) {
            return $prospect->team_id === $user->team_id;
        }

        if ($user->isKepala()) {
            return $prospect->unit_id === $user->unit_id;
        }

        return false;
    }
}

