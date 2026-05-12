<?php

use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\SignupRules\Rules\HasTicketOfTypeRule;
use App\Domain\Event\Models\Event;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketType;
use App\Models\User;

it('is satisfied with no configured ticket types', function () {
    $user = User::factory()->create();
    $competition = Competition::factory()->create();
    $rule = new HasTicketOfTypeRule([]);

    expect($rule->isSatisfiedBy($user, $competition))->toBeTrue();
});

it('is satisfied when the user owns a matching ticket for the event', function () {
    $event = Event::factory()->create();
    $type = TicketType::factory()->create(['event_id' => $event->id]);
    $user = User::factory()->create();
    Ticket::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
        'owner_id' => $user->id,
        'status' => TicketStatus::Active,
    ]);
    $competition = Competition::factory()->create(['event_id' => $event->id]);

    $rule = new HasTicketOfTypeRule(['ticket_type_ids' => [$type->id]]);

    expect($rule->isSatisfiedBy($user, $competition))->toBeTrue();
});

it('is not satisfied when the user has no matching ticket', function () {
    $event = Event::factory()->create();
    $type = TicketType::factory()->create(['event_id' => $event->id]);
    $user = User::factory()->create();
    $competition = Competition::factory()->create(['event_id' => $event->id]);

    $rule = new HasTicketOfTypeRule(['ticket_type_ids' => [$type->id]]);

    expect($rule->isSatisfiedBy($user, $competition))->toBeFalse();
});

it('only counts tickets for the competition event when one is set', function () {
    $eventA = Event::factory()->create();
    $eventB = Event::factory()->create();
    $type = TicketType::factory()->create(['event_id' => $eventA->id]);
    $user = User::factory()->create();
    Ticket::factory()->create([
        'event_id' => $eventB->id,
        'ticket_type_id' => $type->id,
        'owner_id' => $user->id,
        'status' => TicketStatus::Active,
    ]);
    $competition = Competition::factory()->create(['event_id' => $eventA->id]);

    $rule = new HasTicketOfTypeRule(['ticket_type_ids' => [$type->id]]);

    expect($rule->isSatisfiedBy($user, $competition))->toBeFalse();
});
