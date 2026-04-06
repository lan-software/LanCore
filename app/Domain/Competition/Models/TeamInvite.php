<?php

namespace App\Domain\Competition\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamInvite extends Model
{
    protected $fillable = ['team_id', 'invited_by', 'email', 'user_id', 'token', 'accepted_at', 'declined_at', 'expires_at'];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<CompetitionTeam, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(CompetitionTeam::class, 'team_id');
    }

    /** @return BelongsTo<User, $this> */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param Builder<TeamInvite> $query */
    public function scopePending(Builder $query): void
    {
        $query->whereNull('accepted_at')
            ->whereNull('declined_at')
            ->where('expires_at', '>', now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null
            && $this->declined_at === null
            && ! $this->isExpired();
    }
}
