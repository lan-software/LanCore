<?php

namespace App\Domain\DataLifecycle\Actions;

use App\Domain\DataLifecycle\Events\EventSoftDeleted;
use App\Domain\Event\Models\Event;
use Illuminate\Support\Facades\DB;

/**
 * Soft-deletes an event. Hard deletion is forbidden by EventPolicy::forceDelete.
 *
 * @see docs/mil-std-498/SSS.md CAP-DL-008
 * @see docs/mil-std-498/SRS.md DL-F-018
 */
class DeleteEvent
{
    public function execute(Event $event): void
    {
        DB::transaction(function () use ($event): void {
            $event->delete();
            EventSoftDeleted::dispatch($event);
        });
    }
}
