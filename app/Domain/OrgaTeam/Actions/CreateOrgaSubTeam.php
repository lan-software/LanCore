<?php

namespace App\Domain\OrgaTeam\Actions;

use App\Domain\OrgaTeam\Models\OrgaSubTeam;
use App\Domain\OrgaTeam\Models\OrgaTeam;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SSS.md CAP-OT-002
 * @see docs/mil-std-498/SRS.md OT-F-003
 */
class CreateOrgaSubTeam
{
    /**
     * @param  array{name: string, description?: string|null, emoji?: string|null, color?: string|null, sort_order?: int, leader_user_id?: int|null}  $attributes
     */
    public function execute(OrgaTeam $orgaTeam, array $attributes): OrgaSubTeam
    {
        return DB::transaction(static function () use ($orgaTeam, $attributes): OrgaSubTeam {
            $attributes['orga_team_id'] = $orgaTeam->id;
            $attributes['sort_order'] ??= ((int) $orgaTeam->subTeams()->max('sort_order')) + 1;

            return OrgaSubTeam::create($attributes);
        });
    }
}
