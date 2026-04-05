<?php

namespace App\Domain\Orchestration\Models;

use App\Domain\Competition\Models\Competition;
use App\Domain\Games\Models\Game;
use App\Domain\Games\Models\GameMode;
use App\Domain\Orchestration\Enums\OrchestrationJobStatus;
use Database\Factories\OrchestrationJobFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @see docs/mil-std-498/SRS.md ORC-F-006
 */
#[Fillable([
    'game_server_id', 'competition_id', 'lanbrackets_match_id',
    'game_id', 'game_mode_id', 'status', 'match_config',
    'match_handler', 'error_message', 'attempts',
    'started_at', 'completed_at',
])]
class OrchestrationJob extends Model
{
    /** @use HasFactory<OrchestrationJobFactory> */
    use HasFactory;

    protected static function newFactory(): OrchestrationJobFactory
    {
        return OrchestrationJobFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lanbrackets_match_id' => 'integer',
            'status' => OrchestrationJobStatus::class,
            'match_config' => 'array',
            'attempts' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<GameServer, $this> */
    public function gameServer(): BelongsTo
    {
        return $this->belongsTo(GameServer::class);
    }

    /** @return BelongsTo<Competition, $this> */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
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

    /** @return HasMany<MatchChatMessage, $this> */
    public function chatMessages(): HasMany
    {
        return $this->hasMany(MatchChatMessage::class);
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }
}
