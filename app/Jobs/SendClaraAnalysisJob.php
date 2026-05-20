<?php

namespace App\Jobs;

use App\Models\AiRequest;
use App\Services\ClaraIntegrationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendClaraAnalysisJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $aiRequestId
    ) {
    }

    public function handle(ClaraIntegrationService $clara): void
    {
        $request = AiRequest::query()->find($this->aiRequestId);
        if (! $request) {
            return;
        }

        $clara->dispatchRequest($request);
    }
}

