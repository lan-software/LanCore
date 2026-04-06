<?php

namespace App\Domain\Competition\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamJoinRequest extends Model
{
    protected $fillable = ['team_id', 'user_id', 'status', 'message', 'resolved_by', 'resolved_at'];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
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

    /** @return BelongsTo<User, $this> */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /** @param Builder<TeamJoinRequest> $query */
    public function scopePending(Builder $query): void
    {
        $query->where('status', 'pending');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
