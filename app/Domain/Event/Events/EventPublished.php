<?php

namespace App\Domain\Event\Events;

use App\Domain\Event\Models\Event;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EventPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Event $event) {}
}
