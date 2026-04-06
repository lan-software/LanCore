<?php

namespace App\Domain\Competition\Http\Controllers;

use App\Domain\Competition\Models\CompetitionTeam;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserTeamController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $teams = CompetitionTeam::query()
            ->whereHas('activeMembers', fn ($q) => $q->where('user_id', $user->id))
            ->with([
                'competition:id,name,slug,status,type,stage_type,team_size',
                'competition.game:id,name',
                'captain:id,name',
                'activeMembers.user:id,name,email',
            ])
            ->withCount('activeMembers')
            ->get()
            ->map(fn (CompetitionTeam $team) => [
                'id' => $team->id,
                'name' => $team->name,
                'tag' => $team->tag,
                'is_captain' => $team->captain_user_id === $user->id,
                'active_members_count' => $team->active_members_count,
                'competition' => $team->competition ? [
                    'id' => $team->competition->id,
                    'name' => $team->competition->name,
                    'status' => $team->competition->status->value,
                    'type' => $team->competition->type->value,
                    'team_size' => $team->competition->team_size,
                    'game' => $team->competition->game?->name,
                ] : null,
            ]);

        return Inertia::render('competitions/user/Teams', [
            'teams' => $teams,
        ]);
    }

    public function show(Request $request, CompetitionTeam $team): Response
    {
        $user = $request->user();

        $isMember = $team->activeMembers()->where('user_id', $user->id)->exists();
        $isCaptain = $team->captain_user_id === $user->id;

        abort_unless($isMember || $request->user()->can('manageCompetitions'), 403);

        $team->load([
            'competition:id,name,slug,status,type,stage_type,team_size,max_teams',
            'competition.game:id,name',
            'captain:id,name,email',
            'activeMembers.user:id,name,email',
        ]);

        $pendingRequests = $isCaptain
            ? $team->pendingJoinRequests()->with('user:id,name,email')->get()->map(fn ($r) => [
                'id' => $r->id,
                'user_name' => $r->user->name,
                'user_email' => $r->user->email,
                'message' => $r->message,
                'created_at' => $r->created_at->toIso8601String(),
            ])
            : [];

        $pendingInvites = $isCaptain
            ? $team->pendingInvites()->get()->map(fn ($i) => [
                'id' => $i->id,
                'email' => $i->email,
                'expires_at' => $i->expires_at->toIso8601String(),
            ])
            : [];

        return Inertia::render('competitions/user/TeamShow', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'tag' => $team->tag,
                'captain' => $team->captain ? ['id' => $team->captain->id, 'name' => $team->captain->name, 'email' => $team->captain->email] : null,
                'is_captain' => $isCaptain,
                'competition' => $team->competition ? [
                    'id' => $team->competition->id,
                    'name' => $team->competition->name,
                    'status' => $team->competition->status->value,
                    'type' => $team->competition->type->value,
                    'team_size' => $team->competition->team_size,
                    'game' => $team->competition->game?->name,
                ] : null,
                'members' => $team->activeMembers->map(fn ($m) => [
                    'id' => $m->id,
                    'user_id' => $m->user_id,
                    'name' => $m->user?->name,
                    'email' => $m->user?->email,
                    'joined_at' => $m->joined_at?->toIso8601String(),
                    'is_captain' => $m->user_id === $team->captain_user_id,
                ]),
            ],
            'canManage' => $isCaptain,
            'pendingRequests' => $pendingRequests,
            'pendingInvites' => $pendingInvites,
        ]);
    }
}
