<?php

namespace App\Domain\OrgaTeam\Models;

use App\Domain\OrgaTeam\Enums\SubTeamRole;
use App\Models\User;
use Database\Factories\OrgaSubTeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @see docs/mil-std-498/SSS.md CAP-OT-002
 * @see docs/mil-std-498/SRS.md OT-F-003, OT-F-004
 */
#[Fillable(['orga_team_id', 'name', 'description', 'emoji', 'color', 'sort_order', 'leader_user_id'])]
class OrgaSubTeam extends Model
{
    /** @use HasFactory<OrgaSubTeamFactory> */
    use HasFactory;

    protected static function newFactory(): OrgaSubTeamFactory
    {
        return OrgaSubTeamFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function orgaTeam(): BelongsTo
    {
        return $this->belongsTo(OrgaTeam::class);
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_user_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrgaSubTeamMembership::class)->orderBy('sort_order');
    }

    public function deputies(): BelongsToMany
    {
        return $this->users()->wherePivot('role', SubTeamRole::Deputy->value);
    }

    public function members(): BelongsToMany
    {
        return $this->users()->wherePivot('role', SubTeamRole::Member->value);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'orga_sub_team_memberships')
            ->using(OrgaSubTeamMembership::class)
            ->withPivot(['role', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }
}
