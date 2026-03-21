<?php

namespace App\Domain\Games\Actions;

use App\Domain\Games\Models\GameMode;

class DeleteGameMode
{
    public function execute(GameMode $gameMode): void
    {
        $gameMode->delete();
    }
}
