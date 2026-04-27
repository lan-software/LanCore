<?php

namespace App\Domain\OrgaTeam\Actions;

use App\Domain\Event\Models\Event;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SSS.md CAP-OT-003
 * @see docs/mil-std-498/SRS.md OT-F-005
 */
class AssignOrgaTeamToEvent
{
    public function execute(Event $event, ?int $orgaTeamId): void
    {
        DB::transaction(static function () use ($event, $orgaTeamId): void {
            $event->forceFill(['orga_team_id' => $orgaTeamId])->save();
        });
    }
}
