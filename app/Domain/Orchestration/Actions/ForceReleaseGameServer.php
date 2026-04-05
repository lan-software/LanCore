<?php

namespace App\Domain\Orchestration\Actions;

use App\Domain\Orchestration\Enums\GameServerStatus;
use App\Domain\Orchestration\Enums\OrchestrationJobStatus;
use App\Domain\Orchestration\Models\GameServer;

/**
 * Emergency override: force-releases an in-use server and cancels its active job.
 */
class ForceReleaseGameServer
{
    public function execute(GameServer $server): void
    {
        $activeJob = $server->activeOrchestrationJob;

        if ($activeJob !== null) {
            $activeJob->update([
                'status' => OrchestrationJobStatus::Cancelled,
                'error_message' => 'Server force-released by admin.',
            ]);
        }

        $server->update(['status' => GameServerStatus::Available]);
    }
}
