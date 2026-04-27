<?php

namespace App\Domain\OrgaTeam\Models;

use App\Domain\OrgaTeam\Enums\SubTeamRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @see docs/mil-std-498/SRS.md OT-F-004
 */
#[Fillable(['orga_sub_team_id', 'user_id', 'role', 'sort_order'])]
class OrgaSubTeamMembership extends Pivot
{
    protected $table = 'orga_sub_team_memberships';

    public $incrementing = true;

    public $timestamps = true;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => SubTeamRole::class,
            'sort_order' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subTeam(): BelongsTo
    {
        return $this->belongsTo(OrgaSubTeam::class, 'orga_sub_team_id');
    }
}
