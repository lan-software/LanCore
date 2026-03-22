<?php

namespace App\Domain\Announcement\Listeners;

use App\Domain\Announcement\Enums\AnnouncementPriority;
use App\Domain\Announcement\Events\AnnouncementPublished;
use App\Models\User;
use App\Notifications\AnnouncementPublishedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendAnnouncementNotification implements ShouldQueue
{
    public function handle(AnnouncementPublished $event): void
    {
        if ($event->announcement->priority === AnnouncementPriority::Silent) {
            return;
        }

        $users = User::all();

        Notification::send($users, new AnnouncementPublishedNotification($event->announcement));
    }
}
