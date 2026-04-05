<?php

namespace App\Domain\Orchestration\Http\Controllers;

use App\Domain\Orchestration\Actions\HandleTmt2Webhook;
use App\Domain\Orchestration\Models\OrchestrationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives TMT2 webhook events for a specific orchestration job.
 */
class Tmt2WebhookController
{
    public function __construct(
        private readonly HandleTmt2Webhook $handleWebhook,
    ) {}

    public function __invoke(Request $request, OrchestrationJob $orchestrationJob): JsonResponse
    {
        $payload = $request->all();
        $eventType = $payload['type'] ?? 'UNKNOWN';

        $this->handleWebhook->execute($orchestrationJob, $eventType, $payload);

        return response()->json(['status' => 'ok']);
    }
}
