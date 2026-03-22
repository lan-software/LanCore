<?php

namespace App\Domain\Program\Listeners;

use App\Domain\Notification\Models\NotificationPreference;
use App\Domain\Notification\Models\ProgramNotificationSubscription;
use App\Domain\Program\Events\ProgramTimeSlotApproaching;
use App\Domain\Ticketing\Models\Ticket;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

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

        $preferenceEnabledUserIds = NotificationPreference::query()
            ->where('mail_on_program_time_slots', true)
            ->pluck('user_id');

        $usersToNotify = $participantUserIds->filter(function (int $userId) use ($preferenceEnabledUserIds, $subscribedUserIds): bool {
            return $preferenceEnabledUserIds->contains($userId) || $subscribedUserIds->contains($userId);
        });

        $users = User::whereIn('id', $usersToNotify)->get();

        foreach ($users as $user) {
            Log::info('Sending program time slot notification to user', [
                'user_id' => $user->id,
                'time_slot_id' => $timeSlot->id,
                'program_id' => $program->id,
            ]);

            // TODO: Send actual mail notification (e.g. $user->notify(new ProgramTimeSlotNotification($timeSlot)))
        }
    }
}
