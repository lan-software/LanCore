<?php

namespace App\Domain\Notification\Listeners;

use App\Domain\Notification\Events\UserAttributesUpdated;
use App\Notifications\UserAttributesUpdatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendUserAttributesUpdatedNotification implements ShouldQueue
{
    public function handle(UserAttributesUpdated $event): void
    {
        $event->user->notify(new UserAttributesUpdatedNotification(
            changedAttributes: $event->changedAttributes,
        ));
    }
}
