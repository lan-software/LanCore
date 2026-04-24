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

function payloadFromPlan(SeatPlan $plan): array
{
    $plan->load(['blocks.rows', 'blocks.seats', 'blocks.labels', 'blocks.categoryRestrictions']);

    return [
        'blocks' => $plan->blocks->map(fn ($block) => [
            'id' => $block->id,
            'title' => $block->title,
            'color' => $block->color,
            'background_image_url' => $block->background_image_url,
            'sort_order' => $block->sort_order,
            'allowed_ticket_category_ids' => $block->categoryRestrictions->pluck('id')->all(),
            'rows' => $block->rows->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'sort_order' => $row->sort_order,
            ])->all(),
            'seats' => $block->seats->map(fn ($seat) => [
                'id' => $seat->id,
                'row_id' => $seat->seat_plan_row_id,
                'number' => $seat->number,
                'title' => $seat->title,
                'x' => $seat->x,
                'y' => $seat->y,
                'salable' => $seat->salable,
            ])->all(),
            'labels' => $block->labels->map(fn ($label) => [
                'id' => $label->id,
                'title' => $label->title,
                'x' => $label->x,
                'y' => $label->y,
                'sort_order' => $label->sort_order,
            ])->all(),
        ])->all(),
    ];
}

it('returns a saved result with no invalidations when nothing changes', function (): void {
    $event = Event::factory()->create();
    $plan = SeatPlan::factory()->empty()->withBlocks([[
        'title' => 'A',
        'color' => '#fff',
        'rows' => [['name' => 'A', 'seats' => [['number' => 1, 'title' => 'A1', 'x' => 0, 'y' => 0, 'salable' => true]]]],
    ]])->create(['event_id' => $event->id]);

    $result = app(UpdateSeatPlan::class)->execute($plan, ['data' => payloadFromPlan($plan)]);

    expect($result->needsConfirmation())->toBeFalse()
        ->and($result->releasedCount)->toBe(0);
});

it('reports invalidations and writes nothing when a seat is removed without confirmation', function (): void {
    $event = Event::factory()->create();
    $type = TicketType::factory()->create(['event_id' => $event->id]);
    $plan = SeatPlan::factory()->empty()->withBlocks([[
        'title' => 'A',
        'color' => '#fff',
        'rows' => [['name' => 'A', 'seats' => [
            ['number' => 1, 'title' => 'A1', 'x' => 0, 'y' => 0, 'salable' => true],
            ['number' => 2, 'title' => 'A2', 'x' => 1, 'y' => 0, 'salable' => true],
        ]]],
    ]])->create(['event_id' => $event->id]);

    $seatA1 = $plan->seats()->where('title', 'A1')->firstOrFail();

    $ticket = Ticket::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
        'owner_id' => User::factory()->create()->id,
    ]);
    SeatAssignment::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $ticket->owner_id,
        'seat_plan_id' => $plan->id,
        'seat_plan_seat_id' => $seatA1->id,
    ]);

    $payload = payloadFromPlan($plan);
    $payload['blocks'][0]['seats'] = collect($payload['blocks'][0]['seats'])
        ->reject(fn ($seat) => $seat['id'] === $seatA1->id)
        ->values()
        ->all();

    $result = app(UpdateSeatPlan::class)->execute($plan, ['data' => $payload], confirmInvalidations: false);

    expect($result->needsConfirmation())->toBeTrue()
        ->and($result->invalidations)->toHaveCount(1)
        ->and($result->invalidations->first()['reason'])->toBe('seat_removed')
        ->and(SeatAssignment::query()->where('ticket_id', $ticket->id)->count())->toBe(1);

    expect($plan->seats()->count())->toBe(2);
});

it('detects category-mismatch invalidations when the allowlist narrows', function (): void {
    $event = Event::factory()->create();
    $vip = TicketCategory::factory()->create(['event_id' => $event->id, 'name' => 'VIP']);
    $std = TicketCategory::factory()->create(['event_id' => $event->id, 'name' => 'Standard']);
    $type = TicketType::factory()->create([
        'event_id' => $event->id,
        'ticket_category_id' => $vip->id,
    ]);
    $plan = SeatPlan::factory()->empty()->withBlocks([[
        'title' => 'A',
        'color' => '#fff',
        'rows' => [['name' => 'A', 'seats' => [['number' => 1, 'title' => 'A1', 'x' => 0, 'y' => 0, 'salable' => true]]]],
    ]])->create(['event_id' => $event->id]);

    $seatA1 = $plan->seats()->where('title', 'A1')->firstOrFail();

    $ticket = Ticket::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
        'owner_id' => User::factory()->create()->id,
    ]);
    SeatAssignment::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $ticket->owner_id,
        'seat_plan_id' => $plan->id,
        'seat_plan_seat_id' => $seatA1->id,
    ]);

    $payload = payloadFromPlan($plan);
    $payload['blocks'][0]['allowed_ticket_category_ids'] = [$std->id];

    $result = app(UpdateSeatPlan::class)->execute($plan, ['data' => $payload], confirmInvalidations: false);

    expect($result->needsConfirmation())->toBeTrue()
        ->and($result->invalidations->first()['reason'])->toBe('category_mismatch');
});

it('writes and dispatches invalidation events when confirm is true', function (): void {
    EventFacade::fake();

    $event = Event::factory()->create();
    $type = TicketType::factory()->create(['event_id' => $event->id]);
    $plan = SeatPlan::factory()->empty()->withBlocks([[
        'title' => 'A',
        'color' => '#fff',
        'rows' => [['name' => 'A', 'seats' => [
            ['number' => 1, 'title' => 'A1', 'x' => 0, 'y' => 0, 'salable' => true],
            ['number' => 2, 'title' => 'A2', 'x' => 1, 'y' => 0, 'salable' => true],
        ]]],
    ]])->create(['event_id' => $event->id]);

    $seatA1 = $plan->seats()->where('title', 'A1')->firstOrFail();

    $ticket = Ticket::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
        'owner_id' => User::factory()->create()->id,
    ]);
    SeatAssignment::factory()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $ticket->owner_id,
        'seat_plan_id' => $plan->id,
        'seat_plan_seat_id' => $seatA1->id,
    ]);

    $payload = payloadFromPlan($plan);
    $payload['blocks'][0]['seats'] = collect($payload['blocks'][0]['seats'])
        ->reject(fn ($seat) => $seat['id'] === $seatA1->id)
        ->values()
        ->all();

    $result = app(UpdateSeatPlan::class)->execute($plan, ['data' => $payload], confirmInvalidations: true);

    expect($result->needsConfirmation())->toBeFalse()
        ->and($result->releasedCount)->toBe(1)
        ->and(SeatAssignment::query()->where('ticket_id', $ticket->id)->count())->toBe(0)
        ->and($plan->seats()->count())->toBe(1);

    EventFacade::assertDispatched(SeatAssignmentInvalidated::class, 1);
});
