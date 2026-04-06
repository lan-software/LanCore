<?php

namespace App\Domain\Competition\Http\Controllers;

use App\Domain\Competition\Models\CompetitionTeam;
use App\Domain\Competition\Models\CompetitionTeamMember;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminTeamController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CompetitionTeam::class);

        $query = CompetitionTeam::query()
            ->with([
                'competition:id,name,status',
                'competition.game:id,name',
                'captain:id,name',
            ])
            ->withCount('activeMembers');

        if ($search = $request->input('search')) {
            $query->where('name', 'ilike', "%{$search}%");
        }

        if ($competitionId = $request->input('competition_id')) {
            $query->where('competition_id', (int) $competitionId);
        }

        $teams = $query->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20))
            ->withQueryString();

        return Inertia::render('competitions/admin/Teams', [
            'teams' => $teams,
            'filters' => [
                'search' => $request->input('search', ''),
                'competition_id' => $request->input('competition_id', ''),
            ],
        ]);
    }

    public function edit(CompetitionTeam $team): Response
    {
        $this->authorize('manage', $team);

        $team->load([
            'competition:id,name,slug,status,type,stage_type,team_size',
            'competition.game:id,name',
            'captain:id,name,email',
            'activeMembers.user:id,name,email',
            'pendingJoinRequests.user:id,name,email',
            'pendingInvites',
        ]);

        return Inertia::render('competitions/admin/TeamEdit', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'tag' => $team->tag,
                'captain_user_id' => $team->captain_user_id,
                'captain' => $team->captain ? [
                    'id' => $team->captain->id,
                    'name' => $team->captain->name,
                    'email' => $team->captain->email,
                ] : null,
                'competition' => $team->competition ? [
                    'id' => $team->competition->id,
                    'name' => $team->competition->name,
                    'status' => $team->competition->status->value,
                    'type' => $team->competition->type->value,
                    'team_size' => $team->competition->team_size,
                    'game' => $team->competition->game?->name,
                ] : null,
                'members' => $team->activeMembers->map(fn (CompetitionTeamMember $m) => [
                    'id' => $m->id,
                    'user_id' => $m->user_id,
                    'name' => $m->user?->name,
                    'email' => $m->user?->email,
                    'joined_at' => $m->joined_at?->toIso8601String(),
                    'is_captain' => $m->user_id === $team->captain_user_id,
                ]),
                'pending_requests' => $team->pendingJoinRequests->map(fn ($r) => [
                    'id' => $r->id,
                    'user_name' => $r->user->name,
                    'user_email' => $r->user->email,
                    'message' => $r->message,
                    'created_at' => $r->created_at->toIso8601String(),
                ]),
                'pending_invites' => $team->pendingInvites->map(fn ($i) => [
                    'id' => $i->id,
                    'email' => $i->email,
                    'expires_at' => $i->expires_at->toIso8601String(),
                ]),
            ],
        ]);
    }

    public function update(Request $request, CompetitionTeam $team): RedirectResponse
    {
        $this->authorize('manage', $team);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'tag' => ['sometimes', 'nullable', 'string', 'max:10'],
            'captain_user_id' => ['sometimes', 'exists:users,id'],
        ]);

        $team->update($validated);

        return back();
    }

    public function removeMember(CompetitionTeam $team, CompetitionTeamMember $member): RedirectResponse
    {
        $this->authorize('manage', $team);

        $member->update(['left_at' => now()]);

        if ($team->activeMembers()->count() === 0) {
            $team->delete();
        } elseif ($member->user_id === $team->captain_user_id) {
            $nextCaptain = $team->activeMembers()->orderBy('joined_at')->first();
            if ($nextCaptain) {
                $team->update(['captain_user_id' => $nextCaptain->user_id]);
            }
        }

        return back();
    }
}
