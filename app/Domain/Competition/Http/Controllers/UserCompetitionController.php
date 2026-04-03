<?php

namespace App\Domain\Competition\Http\Controllers;

use App\Domain\Competition\Models\Competition;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SRS.md COMP-F-012
 */
class UserCompetitionController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = $request->user()->id;

        $competitions = Competition::query()
            ->whereHas('teams.activeMembers', fn ($q) => $q->where('user_id', $userId))
            ->with(['game', 'event'])
            ->withCount('teams')
            ->orderByDesc('created_at')
            ->paginate(12);

        return Inertia::render('competitions/user/Index', [
            'competitions' => $competitions,
        ]);
    }

    public function show(Request $request, Competition $competition): Response
    {
        $this->authorize('view', $competition);

        $userId = $request->user()->id;

        $competition->load([
            'game',
            'gameMode',
            'event',
            'teams' => fn ($q) => $q->withCount('activeMembers'),
            'teams.captain',
            'teams.activeMembers.user',
        ]);

        $userTeam = $competition->teams
            ->first(fn ($team) => $team->activeMembers->contains('user_id', $userId));

        return Inertia::render('competitions/user/Show', [
            'competition' => $competition,
            'userTeam' => $userTeam,
            'bracketUrl' => $competition->lanBracketsViewUrl(),
        ]);
    }
}
