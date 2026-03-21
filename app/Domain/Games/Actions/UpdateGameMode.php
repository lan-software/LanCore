<?php

namespace App\Domain\Games\Actions;

use App\Domain\Games\Models\GameMode;

class UpdateGameMode
{
    /**
     * @param  array{name: string, slug: string, description?: string|null, team_size: int, parameters?: array<string, mixed>|null, is_active?: bool}  $attributes
     */
    public function execute(GameMode $gameMode, array $attributes): void
    {
        $gameMode->fill($attributes)->save();
    }
}
