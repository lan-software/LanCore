<?php

namespace App\Domain\Games\Actions;

use App\Domain\Games\Models\Game;

class UpdateGame
{
    /**
     * @param  array{name: string, slug: string, publisher?: string|null, description?: string|null, is_active?: bool}  $attributes
     */
    public function execute(Game $game, array $attributes): void
    {
        $game->fill($attributes)->save();
    }
}
