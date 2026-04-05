<?php

namespace App\Domain\Orchestration\Models;

use Database\Factories\MatchChatMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @see docs/mil-std-498/SRS.md ORC-F-012
 */
#[Fillable([
    'orchestration_job_id', 'steam_id', 'player_name',
    'message', 'is_team_chat', 'timestamp',
])]
class MatchChatMessage extends Model
{
    /** @use HasFactory<MatchChatMessageFactory> */
    use HasFactory;

    public $timestamps = false;

    protected static function newFactory(): MatchChatMessageFactory
    {
        return MatchChatMessageFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_team_chat' => 'boolean',
            'timestamp' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<OrchestrationJob, $this> */
    public function orchestrationJob(): BelongsTo
    {
        return $this->belongsTo(OrchestrationJob::class);
    }
}
