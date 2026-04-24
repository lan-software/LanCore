<?php

use App\Domain\Event\Models\Event;
use App\Domain\Seating\Actions\UpdateSeatPlan;
use App\Domain\Seating\Events\SeatAssignmentInvalidated;
use App\Domain\Seating\Models\SeatAssignment;
use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketCategory;
use App\Domain\Ticketing\Models\TicketType;
use App\Models\User;
use Illuminate\Support\Facades\Event as EventFacade;

it('returns a saved result with no invalidations when nothing changes', function (): void {
    $event = Event::factory()->create();
    $plan = SeatPlan::factory()->create([
        'event_id' => $event->id,
        'data' => ['blocks' => [
            ['id' => 'a', 'title' => 'A', 'color' => '#fff', 'seats' => [
                ['id' => 'A1', 'title' => 'A1', 'x' => 0, 'y' => 0, 'salable' => true],
            ]],
        ]],
    ]);

    $result = app(UpdateSeatPlan::class)->execute($plan, ['data' => $plan->data]);

    expect($result->needsConfirmation())->toBeFalse()
        ->and($result->releasedCount)->toBe(0);
});

it('reports invalidations and writes nothing when a seat is removed without confirmation', function (): void {
    $event = Event::factory()->create();
    $type = TicketType::factory()->create(['event_id' => $event->id]);
    $plan = SeatPlan::factory()->create([
        'event_id' => $event->id,
        'data' => ['blocks' => [
            ['id' => 'a', 'title' => 'A', 'color' => '#fff', 'seats' => [
                ['id' => 'A1', 'title' => 'A1', 'x' => 0, 'y' => 0, 'salable' => true],
                ['id' => 'A2', 'title' => 'A2', 'x' => 1, 'y' => 0, 'salable' => true],
            ]],
        ]],
    ]);
    $ticket = Ticket::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
        'owner_id' => User::factory()->create()->id,
    ]);
    SeatAssignment::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $ticket->owner_id,
        'seat_plan_id' => $plan->id,
        'seat_id' => 'A1',
    ]);

    $newData = ['blocks' => [
        ['id' => 'a', 'title' => 'A', 'color' => '#fff', 'seats' => [
            ['id' => 'A2', 'title' => 'A2', 'x' => 1, 'y' => 0, 'salable' => true],
        ]],
    ]];

    $result = app(UpdateSeatPlan::class)->execute($plan, ['data' => $newData], confirmInvalidations: false);

    expect($result->needsConfirmation())->toBeTrue()
        ->and($result->invalidations)->toHaveCount(1)
        ->and($result->invalidations->first()['reason'])->toBe('seat_removed')
        ->and(SeatAssignment::query()->where('ticket_id', $ticket->id)->count())->toBe(1);

    expect($plan->fresh()->data['blocks'][0]['seats'])->toHaveCount(2);
});

it('detects category-mismatch invalidations when the allowlist narrows', function (): void {
    $event = Event::factory()->create();
    $vip = TicketCategory::factory()->create(['event_id' => $event->id, 'name' => 'VIP']);
    $std = TicketCategory::factory()->create(['event_id' => $event->id, 'name' => 'Standard']);
    $type = TicketType::factory()->create([
        'event_id' => $event->id,
        'ticket_category_id' => $vip->id,
    ]);
    $plan = SeatPlan::factory()->create([
        'event_id' => $event->id,
        'data' => ['blocks' => [
            ['id' => 'a', 'title' => 'A', 'color' => '#fff', 'seats' => [
                ['id' => 'A1', 'title' => 'A1', 'x' => 0, 'y' => 0, 'salable' => true],
            ]],
        ]],
    ]);
    $ticket = Ticket::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
        'owner_id' => User::factory()->create()->id,
    ]);
    SeatAssignment::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $ticket->owner_id,
        'seat_plan_id' => $plan->id,
        'seat_id' => 'A1',
    ]);

    $newData = ['blocks' => [
        ['id' => 'a', 'title' => 'A', 'color' => '#fff', 'seats' => [
            ['id' => 'A1', 'title' => 'A1', 'x' => 0, 'y' => 0, 'salable' => true],
        ], 'allowed_ticket_category_ids' => [$std->id]],
    ]];

    $result = app(UpdateSeatPlan::class)->execute($plan, ['data' => $newData], confirmInvalidations: false);

    expect($result->needsConfirmation())->toBeTrue()
        ->and($result->invalidations->first()['reason'])->toBe('category_mismatch');
});

it('writes and dispatches invalidation events when confirm is true', function (): void {
    EventFacade::fake();

    $event = Event::factory()->create();
    $type = TicketType::factory()->create(['event_id' => $event->id]);
    $plan = SeatPlan::factory()->create([
        'event_id' => $event->id,
        'data' => ['blocks' => [
            ['id' => 'a', 'title' => 'A', 'color' => '#fff', 'seats' => [
                ['id' => 'A1', 'title' => 'A1', 'x' => 0, 'y' => 0, 'salable' => true],
                ['id' => 'A2', 'title' => 'A2', 'x' => 1, 'y' => 0, 'salable' => true],
            ]],
        ]],
    ]);
    $ticket = Ticket::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
        'owner_id' => User::factory()->create()->id,
    ]);
    SeatAssignment::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $ticket->owner_id,
        'seat_plan_id' => $plan->id,
        'seat_id' => 'A1',
    ]);

    $newData = ['blocks' => [
        ['id' => 'a', 'title' => 'A', 'color' => '#fff', 'seats' => [
            ['id' => 'A2', 'title' => 'A2', 'x' => 1, 'y' => 0, 'salable' => true],
        ]],
    ]];

    $result = app(UpdateSeatPlan::class)->execute($plan, ['data' => $newData], confirmInvalidations: true);

    expect($result->needsConfirmation())->toBeFalse()
        ->and($result->releasedCount)->toBe(1)
        ->and(SeatAssignment::query()->where('ticket_id', $ticket->id)->count())->toBe(0)
        ->and($plan->fresh()->data['blocks'][0]['seats'])->toHaveCount(1);

    EventFacade::assertDispatched(SeatAssignmentInvalidated::class, 1);
});
