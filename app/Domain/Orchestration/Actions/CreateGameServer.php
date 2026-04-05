<?php

namespace App\Domain\Orchestration\Actions;

use App\Domain\Orchestration\Enums\GameServerStatus;
use App\Domain\Orchestration\Models\GameServer;

/**
 * @see docs/mil-std-498/SRS.md ORC-F-001
 */
class CreateGameServer
{
    /**
     * @param  array{name: string, host: string, port: int, game_id: int, allocation_type: string, game_mode_id?: int|null, credentials?: array<string, mixed>|null, metadata?: array<string, mixed>|null}  $attributes
     */
    public function execute(array $attributes): GameServer
    {
        $attributes['status'] = GameServerStatus::Available;

        return GameServer::create($attributes);
    }
}
