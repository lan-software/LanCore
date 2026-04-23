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

        $bodyKey = $competition
            ? 'competition.notifications.join_request.body_with_competition'
            : 'competition.notifications.join_request.body';

        $bodyParams = $competition
            ? ['user' => $user->name, 'team' => "**{$team->name}**", 'competition' => "**{$competition->name}**"]
            : ['user' => $user->name, 'team' => "**{$team->name}**"];

        return (new MailMessage)
            ->subject(__('competition.notifications.join_request.subject', ['name' => $team->name]))
            ->greeting(__('competition.notifications.join_request.greeting'))
            ->line(__($bodyKey, $bodyParams))
            ->when($this->request->message, fn (MailMessage $mail) => $mail->line(
                __('competition.notifications.join_request.message_line', ['message' => $this->request->message])
            ))
            ->action(__('competition.notifications.join_request.action'), url("/my-teams/{$team->id}"));
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
