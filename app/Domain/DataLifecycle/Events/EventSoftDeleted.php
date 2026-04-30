<?php

namespace App\Domain\DataLifecycle\Events;

use App\Domain\Event\Models\Event;
use Illuminate\Foundation\Events\Dispatchable;

class EventSoftDeleted
{
    use Dispatchable;

    public function __construct(public Event $event) {}
}
