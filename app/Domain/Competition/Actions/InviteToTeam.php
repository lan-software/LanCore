<?php

namespace App\Domain\Competition\Actions;

use App\Domain\Competition\Models\CompetitionTeam;
use App\Domain\Competition\Models\TeamInvite;
use App\Domain\Competition\Notifications\TeamInviteNotification;
use App\Models\User;
use Illuminate\Support\Str;

class InviteToTeam
{
    public function execute(CompetitionTeam $team, User $inviter, string $email): TeamInvite
    {
        $targetUser = User::query()->where('email', $email)->first();

        $invite = TeamInvite::create([
            'team_id' => $team->id,
            'invited_by' => $inviter->id,
            'email' => strtolower($email),
            'user_id' => $targetUser?->id,
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);

        $invite->load('team.competition', 'invitedBy');

        if ($targetUser) {
            $targetUser->notify(new TeamInviteNotification($invite));
        }

        // Always send email (covers unregistered users too)
        \Illuminate\Support\Facades\Mail::send([], [], function ($mail) use ($invite, $email) {
            $team = $invite->team;
            $competition = $team->competition;
            $url = url("/team-invites/{$invite->token}");

            $mail->to($email)
                ->subject("You're invited to join {$team->name}")
                ->html(
                    "<h2>Team Invite</h2>"
                    ."<p><strong>{$invite->invitedBy->name}</strong> has invited you to join team <strong>{$team->name}</strong>"
                    .($competition ? " in <strong>{$competition->name}</strong>" : '')
                    .".</p>"
                    ."<p><a href=\"{$url}\">Accept Invite</a></p>"
                    ."<p>This invite expires on {$invite->expires_at->format('M d, Y')}.</p>"
                );
        });

        return $invite;
    }
}
