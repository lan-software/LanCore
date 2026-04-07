<?php

namespace App\Domain\Program\Listeners;

use App\Domain\Notification\Models\ProgramNotificationSubscription;
use App\Domain\Program\Events\ProgramTimeSlotApproaching;
use App\Domain\Program\Notifications\ProgramTimeSlotNotification;
use App\Domain\Ticketing\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

/**
 * @see docs/mil-std-498/SSS.md CAP-PRG-004
 * @see docs/mil-std-498/SRS.md PRG-F-004
 */
class SendProgramTimeSlotNotification implements ShouldQueue
{
    public function handle(ProgramTimeSlotApproaching $event): void
    {
        $timeSlot = $event->timeSlot;
        $program = $timeSlot->program;
        $eventId = $program->event_id;

        $participantUserIds = Ticket::query()
            ->where('event_id', $eventId)
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->unique();

        $subscribedUserIds = ProgramNotificationSubscription::query()
            ->where('program_id', $program->id)
            ->pluck('user_id');

        $usersToNotify = $participantUserIds->merge($subscribedUserIds)->unique();

        $users = User::whereIn('id', $usersToNotify)->get();

        Notification::send($users, new ProgramTimeSlotNotification($timeSlot));
    }
}
