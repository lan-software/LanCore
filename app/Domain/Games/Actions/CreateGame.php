<?php

namespace App\Domain\Games\Actions;

use App\Domain\Games\Models\Game;

/**
 * @see docs/mil-std-498/SSS.md CAP-GAM-001
 * @see docs/mil-std-498/SRS.md GAM-F-001
 */
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
