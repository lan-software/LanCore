<?php

namespace App\Domain\Competition\Models;

use App\Models\User;
use Database\Factories\CompetitionTeamMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['team_id', 'user_id', 'joined_at', 'left_at'])]
class CompetitionTeamMember extends Model
{
    /** @use HasFactory<CompetitionTeamMemberFactory> */
    use HasFactory;

    protected $table = 'competition_team_members';

    protected static function newFactory(): CompetitionTeamMemberFactory
    {
        return CompetitionTeamMemberFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<CompetitionTeam, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(CompetitionTeam::class, 'team_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
