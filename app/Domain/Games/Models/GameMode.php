<?php

namespace App\Domain\Games\Models;

use Database\Factories\GameModeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['game_id', 'name', 'slug', 'description', 'team_size', 'parameters', 'is_active', 'match_length_minutes'])]
class GameMode extends Model
{
    /** @use HasFactory<GameModeFactory> */
    use HasFactory;

    use HasUlids;

    protected static function newFactory(): GameModeFactory
    {
        return GameModeFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'team_size' => 'integer',
            'parameters' => 'array',
            'is_active' => 'boolean',
            'match_length_minutes' => 'integer',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
