<?php

namespace App\Domain\Notification\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationPreferencesUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly User $user) {}
}
