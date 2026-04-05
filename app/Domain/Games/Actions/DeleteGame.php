<?php

namespace App\Domain\Games\Actions;

use App\Domain\Games\Models\Game;

/**
 * @see docs/mil-std-498/SRS.md GAM-F-001
 */
class DeleteGame
{
    public function execute(Game $game): void
    {
        $game->delete();
    }
}
