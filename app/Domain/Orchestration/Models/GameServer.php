<?php

namespace App\Domain\Orchestration\Models;

use App\Domain\Games\Models\Game;
use App\Domain\Games\Models\GameMode;
use App\Domain\Orchestration\Enums\GameServerAllocationType;
use App\Domain\Orchestration\Enums\GameServerStatus;
use Database\Factories\GameServerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @see docs/mil-std-498/SRS.md ORC-F-002
 */
#[Fillable([
    'name', 'host', 'port', 'game_id', 'game_mode_id',
    'status', 'allocation_type', 'credentials', 'metadata',
])]
class GameServer extends Model
{
    /** @use HasFactory<GameServerFactory> */
    use HasFactory;

    protected static function newFactory(): GameServerFactory
    {
        return GameServerFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'status' => GameServerStatus::class,
            'allocation_type' => GameServerAllocationType::class,
            'credentials' => 'encrypted:array',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<Game, $this> */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /** @return BelongsTo<GameMode, $this> */
    public function gameMode(): BelongsTo
    {
        return $this->belongsTo(GameMode::class);
    }

    /** @return HasMany<OrchestrationJob, $this> */
    public function orchestrationJobs(): HasMany
    {
        return $this->hasMany(OrchestrationJob::class);
    }

    /** @return HasOne<OrchestrationJob, $this> */
    public function activeOrchestrationJob(): HasOne
    {
        return $this->hasOne(OrchestrationJob::class)
            ->whereNotIn('status', [
                'completed',
                'failed',
                'cancelled',
            ]);
    }

    public function isAvailable(): bool
    {
        return $this->status === GameServerStatus::Available;
    }

    public function isInUse(): bool
    {
        return $this->status === GameServerStatus::InUse;
    }
}
