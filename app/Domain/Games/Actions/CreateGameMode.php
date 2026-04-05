<?php

namespace App\Domain\Games\Actions;

use App\Domain\Games\Models\Game;
use App\Domain\Games\Models\GameMode;

/**
 * @see docs/mil-std-498/SSS.md CAP-GAM-002
 * @see docs/mil-std-498/SRS.md GAM-F-002
 */
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
