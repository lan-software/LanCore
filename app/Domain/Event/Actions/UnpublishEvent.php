<?php

namespace App\Domain\Event\Actions;

use App\Domain\Event\Enums\EventStatus;
use App\Domain\Event\Models\Event;
use Illuminate\Support\Facades\DB;

class UnpublishEvent
{
    /**
     * Revert a published event back to draft status.
     *
     * @see docs/mil-std-498/SSS.md CAP-EVT-002
     * @see docs/mil-std-498/SRS.md EVT-F-004
     */
    public function execute(Event $event): void
    {
        DB::transaction(function () use ($event): void {
            $event->status = EventStatus::Draft;
            $event->save();
        });
    }
}
