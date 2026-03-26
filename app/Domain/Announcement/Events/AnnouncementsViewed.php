<?php

namespace App\Domain\Announcement\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnnouncementsViewed
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly User $user) {}
}
