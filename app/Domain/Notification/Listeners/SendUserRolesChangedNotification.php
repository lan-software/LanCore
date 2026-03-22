<?php

namespace App\Domain\Notification\Listeners;

use App\Domain\Notification\Events\UserRolesChanged;
use App\Notifications\UserRolesChangedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendUserRolesChangedNotification implements ShouldQueue
{
    public function handle(UserRolesChanged $event): void
    {
        $event->user->notify(new UserRolesChangedNotification(
            addedRoles: $event->addedRoles,
            removedRoles: $event->removedRoles,
        ));
    }
}
