<?php

namespace App\Domain\Competition\Models;

use App\Domain\Competition\Enums\CompetitionStatus;
use App\Domain\Competition\Enums\CompetitionType;
use App\Domain\Competition\Enums\ResultSubmissionMode;
use App\Domain\Competition\Enums\StageType;
use App\Domain\Event\Models\Event;
use App\Domain\Games\Models\Game;
use App\Domain\Games\Models\GameMode;
use Database\Factories\CompetitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @see docs/mil-std-498/SRS.md COMP-F-001
 */
#[Fillable([
    'name', 'slug', 'description', 'event_id', 'game_id', 'game_mode_id',
    'type', 'stage_type', 'status', 'team_size', 'max_teams',
    'registration_opens_at', 'registration_closes_at', 'starts_at', 'ends_at',
    'lanbrackets_id', 'lanbrackets_share_token', 'settings', 'metadata',
])]
class Competition extends Model
{
    /** @use HasFactory<CompetitionFactory> */
    use HasFactory;

    protected static function newFactory(): CompetitionFactory
    {
        return CompetitionFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CompetitionType::class,
            'stage_type' => StageType::class,
            'status' => CompetitionStatus::class,
            'team_size' => 'integer',
            'max_teams' => 'integer',
            'registration_opens_at' => 'datetime',
            'registration_closes_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'lanbrackets_id' => 'integer',
            'settings' => 'array',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
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

    /** @return HasMany<CompetitionTeam, $this> */
    public function teams(): HasMany
    {
        return $this->hasMany(CompetitionTeam::class);
    }

    /** @return HasMany<MatchResultProof, $this> */
    public function matchResultProofs(): HasMany
    {
        return $this->hasMany(MatchResultProof::class);
    }

    public function isRegistrationOpen(): bool
    {
        return $this->status === CompetitionStatus::RegistrationOpen;
    }

    public function isSyncedToLanBrackets(): bool
    {
        return $this->lanbrackets_id !== null;
    }

    public function lanBracketsViewUrl(): ?string
    {
        if (! $this->isSyncedToLanBrackets() || $this->lanbrackets_share_token === null) {
            return null;
        }

        return rtrim(config('lanbrackets.base_url'), '/')
            .'/overlay/competitions/'.$this->lanbrackets_id
            .'?token='.$this->lanbrackets_share_token;
    }

    public function allowsParticipantResults(): bool
    {
        $mode = $this->settings['result_submission_mode'] ?? ResultSubmissionMode::AdminOnly->value;

        return $mode === ResultSubmissionMode::ParticipantsWithProof->value;
    }
}
