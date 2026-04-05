<?php

namespace App\Domain\Orchestration\Actions;

use App\Domain\Orchestration\Enums\GameServerStatus;
use App\Domain\Orchestration\Models\GameServer;

/**
 * @see docs/mil-std-498/SRS.md ORC-F-010
 */
class ReleaseGameServer
{
    public function execute(GameServer $server): void
    {
        $server->update(['status' => GameServerStatus::Available]);
    }
}
