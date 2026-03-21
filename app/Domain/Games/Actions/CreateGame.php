<?php

namespace App\Domain\Games\Actions;

use App\Domain\Games\Models\Game;

class CreateGame
{
    /**
     * @param  array{name: string, slug: string, publisher?: string|null, description?: string|null, is_active?: bool}  $attributes
     */
    public function execute(array $attributes): Game
    {
        return Game::create($attributes);
    }
}
