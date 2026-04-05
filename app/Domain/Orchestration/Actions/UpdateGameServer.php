<?php

namespace App\Domain\Orchestration\Actions;

use App\Domain\Orchestration\Enums\GameServerStatus;
use App\Domain\Orchestration\Models\GameServer;
use Illuminate\Validation\ValidationException;

/**
 * @see docs/mil-std-498/SRS.md ORC-F-001
 */
class UpdateGameServer
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(GameServer $server, array $attributes): GameServer
    {
        if (isset($attributes['status'])) {
            $newStatus = $attributes['status'] instanceof GameServerStatus
                ? $attributes['status']
                : GameServerStatus::from($attributes['status']);

            if ($server->isInUse() && $newStatus === GameServerStatus::Available) {
                throw ValidationException::withMessages([
                    'status' => 'Cannot set server to available while it is in use.',
                ]);
            }
        }

        $server->update($attributes);

        return $server->refresh();
    }
}
