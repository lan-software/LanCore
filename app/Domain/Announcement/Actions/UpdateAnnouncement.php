<?php

namespace App\Domain\Announcement\Actions;

use App\Domain\Announcement\Events\AnnouncementPublished;
use App\Domain\Announcement\Models\Announcement;

/**
 * @see docs/mil-std-498/SRS.md ANN-F-001
 */
class UpdateAnnouncement
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Announcement $announcement, array $attributes): Announcement
    {
        $wasUnpublished = $announcement->published_at === null;

        $announcement->update($attributes);

        if ($wasUnpublished && $announcement->published_at !== null) {
            AnnouncementPublished::dispatch($announcement);
        }

        return $announcement;
    }
}
