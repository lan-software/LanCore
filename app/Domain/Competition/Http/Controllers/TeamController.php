<?php

namespace App\Domain\Competition\Http\Controllers;

use App\Domain\Competition\Actions\CreateTeam;
use App\Domain\Competition\Actions\JoinTeam;
use App\Domain\Competition\Actions\LeaveTeam;
use App\Domain\Competition\Http\Requests\StoreTeamRequest;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\CompetitionTeam;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function __construct(
        private readonly CreateTeam $createTeam,
        private readonly JoinTeam $joinTeam,
        private readonly LeaveTeam $leaveTeam,
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
