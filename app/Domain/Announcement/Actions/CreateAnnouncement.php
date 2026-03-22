<?php

namespace App\Domain\Announcement\Actions;

use App\Domain\Announcement\Events\AnnouncementPublished;
use App\Domain\Announcement\Models\Announcement;

class CreateAnnouncement
{
    /**
     * @param  array{title: string, description?: string|null, priority: string, event_id: int, author_id: int, published_at?: string|null}  $attributes
     */
    public function execute(array $attributes): Announcement
    {
        $announcement = Announcement::create($attributes);

        if ($announcement->published_at !== null) {
            AnnouncementPublished::dispatch($announcement);
        }

        return $announcement;
    }
}
