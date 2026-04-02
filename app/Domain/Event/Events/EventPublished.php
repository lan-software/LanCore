<?php

namespace App\Domain\Event\Events;

use App\Domain\Event\Models\Event;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * @see docs/mil-std-498/SSS.md CAP-EVT-004
 * @see docs/mil-std-498/SRS.md EVT-F-005
 */
class EventPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Event $event) {}
}
