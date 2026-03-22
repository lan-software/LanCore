<?php

namespace App\Notifications;

use App\Enums\RoleName;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserRolesChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, RoleName>  $addedRoles
     * @param  array<int, RoleName>  $removedRoles
     */
    public function __construct(
        public readonly array $addedRoles = [],
        public readonly array $removedRoles = [],
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Your roles have been updated');

        if ($this->addedRoles) {
            $names = implode(', ', array_map(fn (RoleName $r) => $r->value, $this->addedRoles));
            $message->line('Roles added: '.$names);
        }

        if ($this->removedRoles) {
            $names = implode(', ', array_map(fn (RoleName $r) => $r->value, $this->removedRoles));
            $message->line('Roles removed: '.$names);
        }

        return $message->line('If you have questions about this change, please contact an administrator.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'added_roles' => array_map(fn (RoleName $r) => $r->value, $this->addedRoles),
            'removed_roles' => array_map(fn (RoleName $r) => $r->value, $this->removedRoles),
        ];
    }
}
