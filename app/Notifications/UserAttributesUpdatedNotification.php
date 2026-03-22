<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserAttributesUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $changedAttributes
     */
    public function __construct(public readonly array $changedAttributes) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $fields = implode(', ', array_keys($this->changedAttributes));

        return (new MailMessage)
            ->subject('Your profile has been updated')
            ->line('The following profile fields were updated: '.$fields)
            ->line('If you did not request this change, please contact an administrator.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'changed_attributes' => array_keys($this->changedAttributes),
        ];
    }
}
