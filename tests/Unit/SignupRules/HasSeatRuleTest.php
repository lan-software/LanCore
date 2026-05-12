<?php

use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\SignupRules\Rules\HasSeatRule;
use App\Domain\Event\Models\Event;
use App\Domain\Seating\Models\SeatAssignment;
use App\Domain\Seating\Models\SeatPlan;
use App\Models\User;

it('is satisfied when the user has any assignment in the event', function () {
    $event = Event::factory()->create();
    $plan = SeatPlan::factory()->create(['event_id' => $event->id]);
    $user = User::factory()->create();
    SeatAssignment::factory()->create([
        'user_id' => $user->id,
        'seat_plan_id' => $plan->id,
    ]);

    $competition = Competition::factory()->create(['event_id' => $event->id]);
    $rule = new HasSeatRule;

    expect($rule->isSatisfiedBy($user, $competition))->toBeTrue();
});

it('is not satisfied when the user has no seat for the event', function () {
    $event = Event::factory()->create();
    $user = User::factory()->create();
    $competition = Competition::factory()->create(['event_id' => $event->id]);

    $rule = new HasSeatRule;

    expect($rule->isSatisfiedBy($user, $competition))->toBeFalse();
});

it('does not count assignments from other events', function () {
    $eventA = Event::factory()->create();
    $eventB = Event::factory()->create();
    $planB = SeatPlan::factory()->create(['event_id' => $eventB->id]);
    $user = User::factory()->create();
    SeatAssignment::factory()->create([
        'user_id' => $user->id,
        'seat_plan_id' => $planB->id,
    ]);

    $competition = Competition::factory()->create(['event_id' => $eventA->id]);
    $rule = new HasSeatRule;

    expect($rule->isSatisfiedBy($user, $competition))->toBeFalse();
});
