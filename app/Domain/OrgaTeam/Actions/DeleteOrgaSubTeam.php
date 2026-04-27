<?php

namespace App\Domain\OrgaTeam\Actions;

use App\Domain\OrgaTeam\Models\OrgaSubTeam;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SRS.md OT-F-003
 */
class DeleteOrgaSubTeam
{
    public function execute(OrgaSubTeam $orgaSubTeam): void
    {
        DB::transaction(static function () use ($orgaSubTeam): void {
            $orgaSubTeam->delete();
        });
    }
}
