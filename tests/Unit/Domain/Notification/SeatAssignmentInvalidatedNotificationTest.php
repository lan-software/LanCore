<?php

use App\Domain\Notification\Models\NotificationPreference;
use App\Domain\Notification\Notifications\SeatAssignmentInvalidatedNotification;
use App\Domain\Seating\Events\SeatAssignmentInvalidated;
use App\Domain\Seating\Models\SeatPlan;
use App\Models\User;

function makeNotification(): SeatAssignmentInvalidatedNotification
{
    $plan = SeatPlan::factory()->create();

    return new SeatAssignmentInvalidatedNotification(
        new SeatAssignmentInvalidated(
            ticketId: 1,
            userId: 1,
            seatPlan: $plan,
            previousSeatId: 42,
            previousSeatTitle: 'A1',
            previousBlockId: 7,
            reason: 'seat_removed',
        ),
    );
}

it('defaults to mail + database when the user has no preferences row', function (): void {
    $user = User::factory()->create();
    $user->setRelation('notificationPreference', null);

    expect(makeNotification()->via($user))->toEqualCanonicalizing(['mail', 'database']);
});

it('disables mail when mail_on_seating is false', function (): void {
    $user = User::factory()->create();
    $prefs = NotificationPreference::factory()->create([
        'user_id' => $user->id,
        'mail_on_seating' => false,
        'push_on_seating' => false,
    ]);
    $user->setRelation('notificationPreference', $prefs);

    expect(makeNotification()->via($user))->toEqualCanonicalizing(['database']);
});

it('includes push when push_on_seating is true', function (): void {
    $user = User::factory()->create();
    $prefs = NotificationPreference::factory()->create([
        'user_id' => $user->id,
        'mail_on_seating' => true,
        'push_on_seating' => true,
    ]);
    $user->setRelation('notificationPreference', $prefs);

    expect(makeNotification()->via($user))->toEqualCanonicalizing(['mail', 'database', 'push']);
});

it('toArray produces the documented shape', function (): void {
    $plan = SeatPlan::factory()->create();
    $notification = new SeatAssignmentInvalidatedNotification(
        new SeatAssignmentInvalidated(
            ticketId: 42,
            userId: 7,
            seatPlan: $plan,
            previousSeatId: 13,
            previousSeatTitle: 'B-12',
            previousBlockId: 4,
            reason: 'category_mismatch',
        ),
    );
    $user = User::factory()->create();

    $payload = $notification->toArray($user);

    expect($payload)->toMatchArray([
        'ticket_id' => 42,
        'user_id' => 7,
        'event_id' => $plan->event_id,
        'seat_plan_id' => $plan->id,
        'previous_seat_id' => 13,
        'previous_seat_title' => 'B-12',
        'previous_block_id' => 4,
        'reason' => 'category_mismatch',
    ]);
});
