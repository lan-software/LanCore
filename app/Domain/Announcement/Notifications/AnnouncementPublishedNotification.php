<?php

namespace App\Domain\Announcement\Notifications;

use App\Domain\Announcement\Enums\AnnouncementPriority;
use App\Domain\Announcement\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnnouncementPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Announcement $announcement) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        if ($channel === 'database') {
            return true;
        }

        if ($this->announcement->priority === AnnouncementPriority::Emergency) {
            return true;
        }

        $preferences = $notifiable->notificationPreference;

        if (! $preferences) {
            return true;
        }

        return match ($channel) {
            'mail' => $preferences->mail_on_announcements,
            default => false,
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Announcement: '.$this->announcement->title);

        if ($this->announcement->priority === AnnouncementPriority::Emergency) {
            $message->line('⚠️ EMERGENCY ANNOUNCEMENT');
        }

        $message->line($this->announcement->title);

        if ($this->announcement->description) {
            $message->line($this->announcement->description);
        }

        $message->action('View announcement', url('/'))
            ->line('You can manage your notification preferences in your account settings.');

        return $message;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'announcement_id' => $this->announcement->id,
            'title' => $this->announcement->title,
            'priority' => $this->announcement->priority->value,
        ];
    }
}
