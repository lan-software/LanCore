<?php

namespace App\Domain\Announcement\Listeners;

use App\Domain\Announcement\Events\AnnouncementPublished;
use App\Domain\Webhook\Actions\DispatchWebhooks;
use App\Domain\Webhook\Enums\WebhookEvent;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleAnnouncementPublishedWebhooks implements ShouldQueue
{
    public function __construct(private readonly DispatchWebhooks $dispatchWebhooks) {}

    public function handle(AnnouncementPublished $event): void
    {
        $announcement = $event->announcement;

        $this->dispatchWebhooks->execute(WebhookEvent::AnnouncementPublished, [
            'event' => WebhookEvent::AnnouncementPublished->value,
            'announcement' => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'priority' => $announcement->priority->value,
                'published_at' => $announcement->published_at?->toIso8601String(),
            ],
        ]);
    }
}
