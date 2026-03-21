<?php

namespace App\Domain\Games\Actions;

use App\Domain\Games\Models\Game;

class DeleteGame
{
    public function execute(Game $game): void
    {
        $game->delete();
    }
}
