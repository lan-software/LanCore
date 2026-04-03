<?php

namespace App\Domain\Competition\Models;

use App\Models\User;
use Database\Factories\MatchResultProofFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'competition_id', 'lanbrackets_match_id', 'submitted_by_user_id',
    'submitted_by_team_id', 'screenshot_path', 'scores', 'is_disputed', 'resolved_at',
])]
class MatchResultProof extends Model
{
    /** @use HasFactory<MatchResultProofFactory> */
    use HasFactory;

    protected static function newFactory(): MatchResultProofFactory
    {
        return MatchResultProofFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scores' => 'array',
            'is_disputed' => 'boolean',
            'resolved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Competition, $this> */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /** @return BelongsTo<User, $this> */
    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    /** @return BelongsTo<CompetitionTeam, $this> */
    public function submittedByTeam(): BelongsTo
    {
        return $this->belongsTo(CompetitionTeam::class, 'submitted_by_team_id');
    }
}
