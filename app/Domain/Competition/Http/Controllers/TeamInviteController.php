<?php

namespace App\Domain\Competition\Http\Controllers;

use App\Domain\Competition\Actions\AcceptTeamInvite;
use App\Domain\Competition\Models\TeamInvite;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamInviteController extends Controller
{
    public function show(string $token): Response|RedirectResponse
    {
        $invite = TeamInvite::where('token', $token)
            ->with(['team.competition.game', 'team.activeMembers', 'invitedBy'])
            ->first();

        if (! $invite) {
            abort(404, 'Invite not found.');
        }

        return Inertia::render('team-invites/Show', [
            'invite' => [
                'id' => $invite->id,
                'token' => $invite->token,
                'team' => [
                    'name' => $invite->team->name,
                    'tag' => $invite->team->tag,
                    'members_count' => $invite->team->activeMembers->count(),
                    'team_size' => $invite->team->competition?->team_size,
                ],
                'competition' => $invite->team->competition ? [
                    'name' => $invite->team->competition->name,
                    'game' => $invite->team->competition->game?->name,
                ] : null,
                'invited_by' => $invite->invitedBy->name,
                'is_expired' => $invite->isExpired(),
                'is_pending' => $invite->isPending(),
                'is_accepted' => $invite->accepted_at !== null,
                'is_declined' => $invite->declined_at !== null,
                'expires_at' => $invite->expires_at->toIso8601String(),
            ],
        ]);
    }

    public function accept(Request $request, string $token, AcceptTeamInvite $acceptInvite): RedirectResponse
    {
        $invite = TeamInvite::where('token', $token)->firstOrFail();

        $acceptInvite->execute($invite, $request->user());

        return redirect()->route('my-teams.show', $invite->team_id)
            ->with('success', __('competition.team.joined', ['name' => $invite->team->name]));
    }

    public function decline(string $token): RedirectResponse
    {
        $invite = TeamInvite::where('token', $token)->firstOrFail();

        $invite->update(['declined_at' => now()]);

        return redirect()->route('home')->with('success', __('competition.team.invite_declined'));
    }
}
