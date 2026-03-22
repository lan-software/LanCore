<?php

namespace App\Domain\Announcement\Actions;

use App\Domain\Announcement\Models\Announcement;

class DeleteAnnouncement
{
    public function execute(Announcement $announcement): void
    {
        $announcement->delete();
    }
}
