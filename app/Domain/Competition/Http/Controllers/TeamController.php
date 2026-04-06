<?php

namespace App\Domain\Competition\Http\Controllers;

use App\Domain\Competition\Actions\CreateTeam;
use App\Domain\Competition\Actions\InviteToTeam;
use App\Domain\Competition\Actions\JoinTeam;
use App\Domain\Competition\Actions\LeaveTeam;
use App\Domain\Competition\Actions\RequestToJoinTeam;
use App\Domain\Competition\Actions\ResolveJoinRequest;
use App\Domain\Competition\Http\Requests\StoreTeamRequest;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\CompetitionTeam;
use App\Domain\Competition\Models\TeamJoinRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function __construct(
        private readonly CreateTeam $createTeam,
        private readonly JoinTeam $joinTeam,
        private readonly LeaveTeam $leaveTeam,
        private readonly RequestToJoinTeam $requestToJoin,
        private readonly ResolveJoinRequest $resolveJoinRequest,
        private readonly InviteToTeam $inviteToTeam,
    ) {}

    public function store(StoreTeamRequest $request, Competition $competition): RedirectResponse
    {
        $this->authorize('create', [CompetitionTeam::class, $competition]);

        $this->createTeam->execute($competition, $request->user(), $request->validated());

        return back();
    }

    public function join(Request $request, Competition $competition, CompetitionTeam $team): RedirectResponse
    {
        $this->authorize('join', $team);

        $this->joinTeam->execute($team, $request->user());

        return back();
    }

    public function requestJoin(Request $request, Competition $competition, CompetitionTeam $team): RedirectResponse
    {
        $this->authorize('join', $team);

        $this->requestToJoin->execute($team, $request->user(), $request->input('message'));

        return back()->with('success', 'Join request sent. The team captain will be notified.');
    }

    public function resolveRequest(Request $request, TeamJoinRequest $joinRequest): RedirectResponse
    {
        $this->authorize('manage', $joinRequest->team);

        $action = $request->input('action');

        if ($action === 'approve') {
            $this->resolveJoinRequest->approve($joinRequest, $request->user());
        } else {
            $this->resolveJoinRequest->deny($joinRequest, $request->user());
        }

        return back();
    }

    public function invite(Request $request, Competition $competition, CompetitionTeam $team): RedirectResponse
    {
        $this->authorize('manage', $team);

        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $this->inviteToTeam->execute($team, $request->user(), $request->input('email'));

        return back()->with('success', 'Invite sent.');
    }

    public function leave(Request $request, Competition $competition, CompetitionTeam $team): RedirectResponse
    {
        $this->authorize('leave', $team);

        $this->leaveTeam->execute($team, $request->user());

        return back();
    }

    public function destroy(Competition $competition, CompetitionTeam $team): RedirectResponse
    {
        $this->authorize('manage', $team);

        $team->delete();

        return back();
    }
}
