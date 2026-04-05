<?php

namespace App\Domain\Orchestration\Actions;

use App\Domain\Orchestration\Enums\GameServerStatus;
use App\Domain\Orchestration\Enums\OrchestrationJobStatus;
use App\Domain\Orchestration\Models\OrchestrationJob;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Core orchestration logic: select server, health check, deploy match config.
 *
 * @see docs/mil-std-498/SRS.md ORC-F-007
 */
class ProcessOrchestrationJob
{
    public function __construct(
        private readonly SelectServerForMatch $selectServer,
        private readonly ResolveMatchHandler $resolveHandler,
        private readonly ReleaseGameServer $releaseServer,
    ) {}

    public function execute(OrchestrationJob $job): void
    {
        $job->update(['status' => OrchestrationJobStatus::SelectingServer]);

        $server = DB::transaction(function () use ($job) {
            $server = $this->selectServer->execute($job->game_id, $job->game_mode_id);

            if ($server === null) {
                $job->update([
                    'status' => OrchestrationJobStatus::Failed,
                    'error_message' => "No available game server for game_id {$job->game_id}.",
                    'attempts' => $job->attempts + 1,
                ]);

                return null;
            }

            $server->update(['status' => GameServerStatus::InUse]);
            $job->update(['game_server_id' => $server->id]);

            return $server;
        });

        if ($server === null) {
            return;
        }

        try {
            $job->update(['status' => OrchestrationJobStatus::Deploying]);

            $game = $job->game;
            $handler = $this->resolveHandler->execute($game);

            $job->update(['match_handler' => get_class($handler)]);

            if (! $handler->healthCheck($server)) {
                throw new \RuntimeException('Game server health check failed.');
            }

            $handler->deploy($server, $job->match_config ?? []);

            $job->update([
                'status' => OrchestrationJobStatus::Active,
                'started_at' => now(),
            ]);
        } catch (Throwable $e) {
            $job->update([
                'status' => OrchestrationJobStatus::Failed,
                'error_message' => $e->getMessage(),
                'attempts' => $job->attempts + 1,
            ]);

            $this->releaseServer->execute($server);
        }
    }
}
