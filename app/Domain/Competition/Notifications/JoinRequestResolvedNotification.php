<?php

namespace App\Domain\Competition\Notifications;

use App\Domain\Competition\Models\TeamJoinRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JoinRequestResolvedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly TeamJoinRequest $request,
        private readonly bool $approved,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $team = $this->request->team;
        $status = $this->approved ? 'approved' : 'denied';

        $mail = (new MailMessage)
            ->subject(__('competition.notifications.join_request_resolved.subject', ['status' => $status, 'name' => $team->name]))
            ->greeting($this->approved
                ? __('competition.notifications.join_request_resolved.greeting_approved')
                : __('competition.notifications.join_request_resolved.greeting_denied')
            );

        if ($this->approved) {
            $mail->line(__('competition.notifications.join_request_resolved.approved_line', ['team' => "**{$team->name}**"]))
                ->action(__('competition.notifications.join_request_resolved.action_view_team'), url("/my-teams/{$team->id}"));
        } else {
            $mail->line(__('competition.notifications.join_request_resolved.denied_line', ['team' => "**{$team->name}**"]));
        }

        return $mail;
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'join_request_resolved',
            'request_id' => $this->request->id,
            'team_id' => $this->request->team_id,
            'team_name' => $this->request->team->name,
            'competition_name' => $this->request->team->competition?->name,
            'approved' => $this->approved,
            'url' => $this->approved ? "/my-teams/{$this->request->team_id}" : '/my-competitions',
        ];
    }
}
