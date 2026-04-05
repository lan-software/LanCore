<?php

namespace App\Domain\Orchestration\Actions;

use App\Domain\Competition\Services\LanBracketsClient;
use App\Domain\Orchestration\Contracts\Features\SupportsChatFeature;
use App\Domain\Orchestration\Models\OrchestrationJob;
use Illuminate\Support\Facades\Log;

/**
 * Processes incoming TMT2 webhook events for a specific orchestration job.
 *
 * Handles MATCH_END (auto-reports to LanBrackets), CHAT (feature dispatch),
 * and logs other events for observability.
 */
class HandleTmt2Webhook
{
    public function __construct(
        private readonly CompleteOrchestrationJob $completeJob,
        private readonly ResolveMatchHandler $resolveHandler,
        private readonly LanBracketsClient $lanBracketsClient,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(OrchestrationJob $job, string $eventType, array $payload): void
    {
        match ($eventType) {
            'MATCH_END' => $this->handleMatchEnd($job, $payload),
            'CHAT' => $this->handleChat($job, $payload),
            default => Log::debug("TMT2 webhook [{$eventType}] for orchestration job {$job->id}"),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleMatchEnd(OrchestrationJob $job, array $payload): void
    {
        $this->autoReportToLanBrackets($job, $payload);

        $this->completeJob->execute($job);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleChat(OrchestrationJob $job, array $payload): void
    {
        try {
            $handler = $this->resolveHandler->execute($job->game);

            if ($handler instanceof SupportsChatFeature) {
                $handler->handleChatMessage($job, $payload);
            }
        } catch (\Throwable $e) {
            Log::warning("Chat handling failed for job {$job->id}: {$e->getMessage()}");
        }
    }

    /**
     * Maps TMT2 MATCH_END scores to LanBrackets participant scores
     * and auto-reports the result.
     *
     * @param  array<string, mixed>  $payload
     */
    private function autoReportToLanBrackets(OrchestrationJob $job, array $payload): void
    {
        $competition = $job->competition;

        if (! $competition->isSyncedToLanBrackets()) {
            return;
        }

        try {
            $wonMapsTeamA = $payload['wonMapsTeamA'] ?? 0;
            $wonMapsTeamB = $payload['wonMapsTeamB'] ?? 0;

            $scores = [
                1 => (int) $wonMapsTeamA,
                2 => (int) $wonMapsTeamB,
            ];

            $this->lanBracketsClient->reportMatchResult(
                $competition->lanbrackets_id,
                $job->lanbrackets_match_id,
                $scores
            );
        } catch (\Throwable $e) {
            Log::error("Auto-report to LanBrackets failed for job {$job->id}: {$e->getMessage()}");
        }
    }
}
