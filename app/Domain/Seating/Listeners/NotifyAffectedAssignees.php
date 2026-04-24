<?php

namespace App\Domain\Seating\Listeners;

use App\Domain\Notification\Notifications\SeatAssignmentInvalidatedNotification;
use App\Domain\Seating\Events\SeatAssignmentInvalidated;
use App\Domain\Ticketing\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

/**
 * Queued listener that fans out a SeatAssignmentInvalidatedNotification to
 * every party with an interest in the released seat: ticket owner, ticket
 * manager, and the assignee themselves (deduped, null-safe).
 *
 * @see docs/mil-std-498/SRS.md SET-F-014
 */
class NotifyAffectedAssignees implements ShouldQueue
{
    public function handle(SeatAssignmentInvalidated $event): void
    {
        $ticket = Ticket::query()->with(['owner', 'manager'])->find($event->ticketId);

        if ($ticket === null) {
            return;
        }

        $assignee = User::query()->find($event->userId);

        $recipients = collect([$ticket->owner, $ticket->manager, $assignee])
            ->filter(fn ($user): bool => $user instanceof User)
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new SeatAssignmentInvalidatedNotification($event));
    }
}
