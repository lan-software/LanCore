<?php

namespace App\Domain\OrgaTeam\Actions;

use App\Domain\OrgaTeam\Models\OrgaSubTeam;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SRS.md OT-F-003
 */
class UpdateOrgaSubTeam
{
    /**
     * @param  array{name?: string, description?: string|null, emoji?: string|null, color?: string|null, sort_order?: int, leader_user_id?: int|null}  $attributes
     */
    public function execute(OrgaSubTeam $orgaSubTeam, array $attributes): void
    {
        DB::transaction(static function () use ($orgaSubTeam, $attributes): void {
            $orgaSubTeam->fill($attributes)->save();
        });
    }
}
