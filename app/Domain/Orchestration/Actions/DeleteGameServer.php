<?php

namespace App\Domain\Orchestration\Actions;

use App\Domain\Orchestration\Models\GameServer;
use Illuminate\Validation\ValidationException;

/**
 * @see docs/mil-std-498/SRS.md ORC-F-001
 */
class DeleteGameServer
{
    public function execute(GameServer $server): void
    {
        if ($server->isInUse()) {
            throw ValidationException::withMessages([
                'server' => 'Cannot delete a server that is currently in use.',
            ]);
        }

        $server->delete();
    }
}
