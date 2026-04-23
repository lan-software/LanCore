<?php

use App\Domain\Event\Models\Event;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketType;
use App\Models\User;

it('renders the privacy settings page with the current visibility flag', function (): void {
    $user = User::factory()->create(['is_seat_visible_publicly' => false]);

    $this->actingAs($user)
        ->get('/settings/privacy')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/Privacy')
            ->where('isSeatVisiblePublicly', false),
        );
});

it('updates the privacy flag', function (): void {
    $user = User::factory()->create(['is_seat_visible_publicly' => true]);

    $this->actingAs($user)
        ->patch('/settings/privacy', ['is_seat_visible_publicly' => false])
        ->assertRedirect();

    expect($user->refresh()->is_seat_visible_publicly)->toBeFalse();
});

it('redacts hidden users names for anonymous viewers but reveals for same-event ticket holders', function (): void {
    $event = Event::factory()->create();
    $ticketType = TicketType::factory()->create([
        'event_id' => $event->id,
        'max_users_per_ticket' => 1,
    ]);

    $hiddenUser = User::factory()->create([
        'name' => 'Hidden Person',
        'is_seat_visible_publicly' => false,
    ]);

    // Anonymous viewer cannot see the name
    expect($hiddenUser->isSeatNameVisibleTo(null, $event))->toBeFalse();

    // Random logged-in viewer with no ticket cannot see
    $stranger = User::factory()->create();
    expect($hiddenUser->isSeatNameVisibleTo($stranger, $event))->toBeFalse();

    // A user holding a ticket for the same event CAN see
    $attendee = User::factory()->create();
    Ticket::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $ticketType->id,
        'owner_id' => $attendee->id,
    ]);
    expect($hiddenUser->isSeatNameVisibleTo($attendee, $event))->toBeTrue();
});
