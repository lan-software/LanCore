<?php

namespace App\Domain\Announcement\Actions;

use App\Domain\Announcement\Models\Announcement;

/**
 * @see docs/mil-std-498/SRS.md ANN-F-001
 */
class DeleteAnnouncement
{
    public function execute(Announcement $announcement): void
    {
        $announcement->delete();
    }
}
