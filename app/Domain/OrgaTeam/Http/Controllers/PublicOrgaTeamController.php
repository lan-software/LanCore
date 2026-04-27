<?php

namespace App\Domain\OrgaTeam\Http\Controllers;

use App\Domain\Event\Models\Event;
use App\Domain\OrgaTeam\Http\Resources\OrgaTeamResource;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SSS.md CAP-OT-004
 * @see docs/mil-std-498/SRS.md OT-F-007
 */
class PublicOrgaTeamController extends Controller
{
    public function show(Event $event): Response
    {
        abort_if($event->orga_team_id === null, 404);

        $event->load([
            'orgaTeam.organizer',
            'orgaTeam.deputies',
            'orgaTeam.subTeams.leader',
            'orgaTeam.subTeams.memberships.user',
        ]);

        return Inertia::render('orga-teams/Public', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'start_date' => $event->start_date?->toIso8601String(),
                'end_date' => $event->end_date?->toIso8601String(),
            ],
            'orgaTeam' => OrgaTeamResource::make($event->orgaTeam)->resolve(),
        ]);
    }
}
