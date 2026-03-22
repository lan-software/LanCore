<?php

namespace App\Domain\Announcement\Events;

use App\Domain\Announcement\Models\Announcement;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnnouncementPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Announcement $announcement) {}
}
