<?php

namespace App\Domain\Games\Actions;

use App\Domain\Games\Models\Game;
use App\Domain\Games\Models\GameMode;

class CreateGameMode
{
    /**
     * @param  array{name: string, slug: string, description?: string|null, team_size: int, parameters?: array<string, mixed>|null, is_active?: bool}  $attributes
     */
    public function execute(Game $game, array $attributes): GameMode
    {
        return $game->gameModes()->create($attributes);
    }
}
