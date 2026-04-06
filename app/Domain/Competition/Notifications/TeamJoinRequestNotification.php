<?php

namespace App\Domain\Competition\Notifications;

use App\Domain\Competition\Models\TeamJoinRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamJoinRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly TeamJoinRequest $request) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $user = $this->request->user;
        $team = $this->request->team;
        $competition = $team->competition;

        return (new MailMessage)
            ->subject("Join request for {$team->name}")
            ->greeting("New join request")
            ->line("{$user->name} wants to join your team **{$team->name}**"
                .($competition ? " in **{$competition->name}**" : '').'.')
            ->when($this->request->message, fn (MailMessage $mail) => $mail->line("Message: \"{$this->request->message}\""))
            ->action('Review Request', url("/my-teams/{$team->id}"));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'team_join_request',
            'request_id' => $this->request->id,
            'team_id' => $this->request->team_id,
            'team_name' => $this->request->team->name,
            'user_name' => $this->request->user->name,
            'competition_name' => $this->request->team->competition?->name,
            'message' => $this->request->message,
            'url' => "/my-teams/{$this->request->team_id}",
        ];
    }
}
