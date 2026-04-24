<?php

use App\Domain\Event\Models\Event;
use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Seating\Models\SeatPlanSeat;

it('can create a seat plan for an event', function () {
    $event = Event::factory()->create();

    $seatPlan = SeatPlan::factory()->create(['event_id' => $event->id]);

    expect($seatPlan->event->id)->toBe($event->id);
    expect($seatPlan->blocks()->exists())->toBeTrue();
});

it('stores blocks with seats and labels in normalized tables', function () {
    $seatPlan = SeatPlan::factory()->empty()->withBlocks([
        [
            'title' => 'Orchestra',
            'color' => '#2c3e50',
            'rows' => [
                ['name' => 'A', 'seats' => [
                    ['number' => 1, 'title' => 'A1', 'x' => 0, 'y' => 0, 'salable' => true],
                    ['number' => 2, 'title' => 'A2', 'x' => 30, 'y' => 0, 'salable' => true],
                ]],
            ],
            'labels' => [['title' => 'Row A', 'x' => -30, 'y' => 0]],
        ],
        [
            'title' => 'Balcony',
            'color' => '#34495e',
            'rows' => [
                ['name' => 'B', 'seats' => [
                    ['number' => 1, 'title' => 'B1', 'x' => 0, 'y' => 0, 'salable' => true],
                ]],
            ],
            'labels' => [],
        ],
    ])->create();

    $blocks = $seatPlan->blocks()->with(['seats', 'labels'])->get();

    expect($blocks)->toHaveCount(2);
    expect($blocks[0]->title)->toBe('Orchestra');
    expect($blocks[0]->seats)->toHaveCount(2);
    expect($blocks[0]->labels)->toHaveCount(1);
    expect($blocks[1]->title)->toBe('Balcony');
});

it('stores detailed seat properties', function () {
    $seatPlan = SeatPlan::factory()->empty()->withBlocks([
        [
            'title' => 'Main',
            'color' => '#4CAF50',
            'rows' => [
                ['name' => 'A', 'seats' => [[
                    'number' => 1,
                    'title' => 'A1',
                    'x' => 100,
                    'y' => 200,
                    'salable' => true,
                    'note' => 'Wheelchair accessible',
                    'color' => '#4CAF50',
                    'custom_data' => ['price' => 50.00, 'category' => 'premium'],
                ]]],
            ],
            'labels' => [],
        ],
    ])->create();

    /** @var SeatPlanSeat $seat */
    $seat = $seatPlan->seats()->first();

    expect($seat->title)->toBe('A1');
    expect($seat->x)->toBe(100);
    expect($seat->note)->toBe('Wheelchair accessible');
    expect($seat->custom_data['category'])->toBe('premium');
});

it('belongs to an event', function () {
    $seatPlan = SeatPlan::factory()->create();

    expect($seatPlan->event)->toBeInstanceOf(Event::class);
});

it('an event can have multiple seat plans', function () {
    $event = Event::factory()->create();

    SeatPlan::factory()->count(3)->create(['event_id' => $event->id]);

    expect($event->seatPlans)->toHaveCount(3);
});

it('cascades deletion when event is deleted', function () {
    $event = Event::factory()->create();
    SeatPlan::factory()->count(2)->create(['event_id' => $event->id]);

    $event->delete();

    expect(SeatPlan::where('event_id', $event->id)->count())->toBe(0);
});

it('has no blocks when created with the empty state', function () {
    $seatPlan = SeatPlan::factory()->empty()->create();

    expect($seatPlan->blocks()->count())->toBe(0);
    expect($seatPlan->seats()->count())->toBe(0);
});
