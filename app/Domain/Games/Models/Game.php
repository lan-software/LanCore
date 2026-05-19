<?php

namespace App\Domain\Games\Models;

use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'publisher', 'description', 'is_active', 'signup_rules', 'avg_match_minutes', 'avg_stage_minutes', 'match_length_minutes'])]
class Game extends Model
{
    /** @use HasFactory<GameFactory> */
    use HasFactory;

    use HasUlids;

    protected static function newFactory(): GameFactory
    {
        return GameFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'signup_rules' => 'array',
            'avg_match_minutes' => 'integer',
            'avg_stage_minutes' => 'integer',
            'match_length_minutes' => 'integer',
        ];
    }

    public function gameModes(): HasMany
    {
        return $this->hasMany(GameMode::class);
    }
}
