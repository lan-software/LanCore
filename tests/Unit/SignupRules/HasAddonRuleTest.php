<?php

use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\SignupRules\Rules\HasAddonRule;
use App\Domain\Event\Models\Event;
use App\Domain\Shop\Models\Order;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Addon;
use App\Domain\Ticketing\Models\Ticket;
use App\Models\User;

it('is satisfied with no configured addons', function () {
    $user = User::factory()->create();
    $competition = Competition::factory()->create();
    $rule = new HasAddonRule([]);

    expect($rule->isSatisfiedBy($user, $competition))->toBeTrue();
});

it('is satisfied when the user owns a ticket with one of the addons for the event', function () {
    $event = Event::factory()->create();
    $addon = Addon::factory()->create(['event_id' => $event->id]);
    $user = User::factory()->create();
    $ticket = Ticket::factory()->create([
        'event_id' => $event->id,
        'owner_id' => $user->id,
        'status' => TicketStatus::Active,
    ]);
    $order = Order::factory()->create();
    $ticket->addons()->attach($addon->id, ['price_paid' => 0, 'order_id' => $order->id]);

    $competition = Competition::factory()->create(['event_id' => $event->id]);
    $rule = new HasAddonRule(['addon_ids' => [$addon->id]]);

    expect($rule->isSatisfiedBy($user, $competition))->toBeTrue();
});

it('is not satisfied when none of the required addons are present', function () {
    $event = Event::factory()->create();
    $addon = Addon::factory()->create(['event_id' => $event->id]);
    $user = User::factory()->create();
    Ticket::factory()->create([
        'event_id' => $event->id,
        'owner_id' => $user->id,
        'status' => TicketStatus::Active,
    ]);

    $competition = Competition::factory()->create(['event_id' => $event->id]);
    $rule = new HasAddonRule(['addon_ids' => [$addon->id]]);

    expect($rule->isSatisfiedBy($user, $competition))->toBeFalse();
});
