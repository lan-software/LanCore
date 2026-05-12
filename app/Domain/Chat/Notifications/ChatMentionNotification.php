<?php

namespace App\Domain\Chat\Notifications;

use App\Domain\Chat\Models\ChatMessage;
use App\Domain\Notification\Channels\WebPushChannel;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * @see docs/mil-std-498/SRS.md CHT-F-014..017
 */
class ChatMentionNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly ChatMessage $message) {}

    /**
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        $channels = ['database'];

        $prefs = $notifiable->notificationPreference;

        if ($prefs?->mail_on_chat_mention ?? true) {
            $channels[] = 'mail';
        }

        if ($prefs?->push_on_chat_mention ?? false) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('chat.notifications.mention.subject'))
            ->greeting(__('chat.notifications.mention.greeting', ['name' => $notifiable->name]))
            ->line(__('chat.notifications.mention.intro', ['username' => $this->message->user->username ?? $this->message->user->name]))
            ->line($this->message->body);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'message_id' => $this->message->id,
            'room_id' => $this->message->room_id,
            'author_id' => $this->message->user_id,
            'body' => $this->message->body,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toWebPush(User $notifiable): array
    {
        return [
            'title' => __('chat.notifications.mention.push_title'),
            'body' => $this->message->body,
            'data' => [
                'message_id' => $this->message->id,
                'room_id' => $this->message->room_id,
            ],
        ];
    }
}
