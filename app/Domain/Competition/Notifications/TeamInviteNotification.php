<?php

namespace App\Domain\Competition\Notifications;

use App\Domain\Competition\Models\TeamInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamInviteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly TeamInvite $invite) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $team = $this->invite->team;

        return [
            'type' => 'team_invite',
            'invite_id' => $this->invite->id,
            'team_id' => $team->id,
            'team_name' => $team->name,
            'competition_name' => $team->competition?->name,
            'invited_by' => $this->invite->invitedBy->name,
            'url' => "/team-invites/{$this->invite->token}",
            'expires_at' => $this->invite->expires_at->toIso8601String(),
        ];
    }
}
