<?php

namespace App\Domain\Orchestration\Actions;

use App\Domain\Orchestration\Enums\GameServerStatus;
use App\Domain\Orchestration\Models\GameServer;
use Illuminate\Support\Facades\DB;

/**
 * Selects the best available game server for a match.
 *
 * Priority: Competition > Flexible > Casual.
 * Uses pessimistic locking to prevent race conditions.
 *
 * @see docs/mil-std-498/SRS.md ORC-F-005
 */
class SelectServerForMatch
{
    public function execute(int $gameId, ?int $gameModeId = null): ?GameServer
    {
        return DB::transaction(function () use ($gameId, $gameModeId) {
            $query = GameServer::query()
                ->where('game_id', $gameId)
                ->where('status', GameServerStatus::Available)
                ->orderByRaw("CASE allocation_type
                    WHEN 'competition' THEN 1
                    WHEN 'flexible' THEN 2
                    WHEN 'casual' THEN 3
                    ELSE 4
                END");

            if ($gameModeId !== null) {
                $query->orderByRaw('CASE WHEN game_mode_id = ? THEN 0 WHEN game_mode_id IS NULL THEN 1 ELSE 2 END', [$gameModeId]);
            }

            return $query->lockForUpdate()->first();
        });
    }
}
