<?php

namespace App\Domain\OrgaTeam\Models;

use App\Domain\Event\Models\Event;
use App\Models\User;
use Database\Factories\OrgaTeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @see docs/mil-std-498/SSS.md CAP-OT-001
 * @see docs/mil-std-498/SRS.md OT-F-001, OT-F-002
 */
#[Fillable(['name', 'slug', 'description', 'organizer_user_id'])]
class OrgaTeam extends Model
{
    /** @use HasFactory<OrgaTeamFactory> */
    use HasFactory;

    protected static function newFactory(): OrgaTeamFactory
    {
        return OrgaTeamFactory::new();
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_user_id');
    }

    public function deputies(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'orga_team_deputies')
            ->withPivot(['sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function subTeams(): HasMany
    {
        return $this->hasMany(OrgaSubTeam::class)->orderBy('sort_order');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
