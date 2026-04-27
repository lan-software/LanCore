<?php

namespace App\Domain\OrgaTeam\Actions;

use App\Domain\OrgaTeam\Models\OrgaTeam;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SRS.md OT-F-001, OT-F-005
 */
class DeleteOrgaTeam
{
    public function execute(OrgaTeam $orgaTeam): void
    {
        DB::transaction(static function () use ($orgaTeam): void {
            $orgaTeam->delete();
        });
    }
}
