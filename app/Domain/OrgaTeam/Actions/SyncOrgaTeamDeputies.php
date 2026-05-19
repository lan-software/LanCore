<?php

namespace App\Domain\OrgaTeam\Actions;

use App\Domain\OrgaTeam\Models\OrgaTeam;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SRS.md OT-F-002
 */
class SyncOrgaTeamDeputies
{
    /**
     * Replace the team's deputies with the given user IDs, preserving the input order via `sort_order`.
     * The team's organizer is automatically excluded.
     *
     * @param  array<int, string>  $userIds
     */
    public function execute(OrgaTeam $orgaTeam, array $userIds): void
    {
        $sync = [];
        $position = 0;
        $organizerId = (string) $orgaTeam->organizer_user_id;
        foreach ($userIds as $userId) {
            $userId = (string) $userId;
            if ($userId === $organizerId) {
                continue;
            }
            $sync[$userId] = ['sort_order' => $position++];
        }

        DB::transaction(static function () use ($orgaTeam, $sync): void {
            $orgaTeam->deputies()->sync($sync);
        });
    }
}
