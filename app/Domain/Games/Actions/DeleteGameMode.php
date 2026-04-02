<?php

namespace App\Domain\Games\Actions;

use App\Domain\Games\Models\GameMode;

/**
 * @see docs/mil-std-498/SRS.md GAM-F-002
 */
class DeleteGameMode
{
    public function execute(GameMode $gameMode): void
    {
        $gameMode->delete();
    }
}
