<?php

namespace App\Domain\OrgaTeam\Actions;

use App\Domain\OrgaTeam\Enums\SubTeamRole;
use App\Domain\OrgaTeam\Models\OrgaSubTeam;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SRS.md OT-F-004
 */
class SyncOrgaSubTeamMemberships
{
    /**
     * Replace the sub-team's memberships with the given (user_id, role) tuples.
     * The team's organizer is automatically excluded; sort_order follows input order.
     *
     * @param  array<int, array{user_id: int, role: string|SubTeamRole}>  $memberships
     */
    public function execute(OrgaSubTeam $orgaSubTeam, array $memberships): void
    {
        $organizerId = (int) $orgaSubTeam->orgaTeam()->value('organizer_user_id');

        $sync = [];
        $position = 0;
        foreach ($memberships as $membership) {
            $userId = (int) $membership['user_id'];
            if ($userId === $organizerId) {
                continue;
            }
            $role = $membership['role'] instanceof SubTeamRole
                ? $membership['role']
                : SubTeamRole::from($membership['role']);

            $sync[$userId] = [
                'role' => $role->value,
                'sort_order' => $position++,
            ];
        }

        DB::transaction(static function () use ($orgaSubTeam, $sync): void {
            $orgaSubTeam->users()->sync($sync);
        });
    }
}
