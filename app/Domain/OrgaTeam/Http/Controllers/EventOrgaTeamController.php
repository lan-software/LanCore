<?php

namespace App\Domain\OrgaTeam\Http\Controllers;

use App\Domain\Event\Models\Event;
use App\Domain\OrgaTeam\Actions\AssignOrgaTeamToEvent;
use App\Domain\OrgaTeam\Http\Requests\AssignOrgaTeamToEventRequest;
use App\Domain\OrgaTeam\Models\OrgaTeam;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

/**
 * @see docs/mil-std-498/SSS.md CAP-OT-003
 * @see docs/mil-std-498/SRS.md OT-F-005
 */
class EventOrgaTeamController extends Controller
{
    public function __construct(
        private readonly AssignOrgaTeamToEvent $assign,
    ) {}

    public function update(AssignOrgaTeamToEventRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('assignToEvent', OrgaTeam::class);

        $this->assign->execute($event, $request->validated('orga_team_id'));

        return back();
    }
}
