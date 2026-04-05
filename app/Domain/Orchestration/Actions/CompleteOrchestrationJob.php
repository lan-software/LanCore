<?php

namespace App\Domain\Orchestration\Actions;

use App\Domain\Orchestration\Enums\OrchestrationJobStatus;
use App\Domain\Orchestration\Models\OrchestrationJob;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Completes an orchestration job: teardown match handler and release server.
 *
 * @see docs/mil-std-498/SRS.md ORC-F-010
 */
class CompleteOrchestrationJob
{
    public function __construct(
        private readonly ResolveMatchHandler $resolveHandler,
        private readonly ReleaseGameServer $releaseServer,
    ) {}

    public function execute(OrchestrationJob $job): void
    {
        if ($job->status !== OrchestrationJobStatus::Active) {
            return;
        }

        $server = $job->gameServer;

        if ($server !== null && $job->match_handler !== null) {
            try {
                $handler = $this->resolveHandler->execute($job->game);
                $handler->teardown($server, $job->match_config ?? []);
            } catch (Throwable $e) {
                Log::warning("Teardown failed for orchestration job {$job->id}: {$e->getMessage()}");
            }
        }

        $job->update([
            'status' => OrchestrationJobStatus::Completed,
            'completed_at' => now(),
        ]);

        if ($server !== null) {
            $this->releaseServer->execute($server);
        }
    }
}
