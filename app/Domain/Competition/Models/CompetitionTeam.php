<?php

namespace App\Domain\Competition\Models;

use App\Models\User;
use Database\Factories\CompetitionTeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['competition_id', 'name', 'tag', 'captain_user_id', 'lanbrackets_id'])]
class CompetitionTeam extends Model
{
    /** @use HasFactory<CompetitionTeamFactory> */
    use HasFactory;

    protected $table = 'competition_teams';

    protected static function newFactory(): CompetitionTeamFactory
    {
        return CompetitionTeamFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lanbrackets_id' => 'integer',
        ];
    }

    /** @return BelongsTo<Competition, $this> */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /** @return BelongsTo<User, $this> */
    public function captain(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captain_user_id');
    }

    /** @return HasMany<CompetitionTeamMember, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(CompetitionTeamMember::class, 'team_id');
    }

    /** @return HasMany<CompetitionTeamMember, $this> */
    public function activeMembers(): HasMany
    {
        return $this->members()->whereNull('left_at');
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'competition_team_members', 'team_id', 'user_id')
            ->wherePivotNull('left_at')
            ->withPivot('joined_at', 'left_at')
            ->withTimestamps();
    }

    public function isFull(): bool
    {
        if ($this->competition->team_size === null) {
            return false;
        }

        return $this->activeMembers()->count() >= $this->competition->team_size;
    }

    public function hasMember(User $user): bool
    {
        return $this->activeMembers()->where('user_id', $user->id)->exists();
    }
}
