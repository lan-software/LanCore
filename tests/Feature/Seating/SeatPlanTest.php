<?php

use App\Domain\Event\Models\Event;
use App\Domain\Seating\Models\SeatPlan;

it('can create a seat plan for an event', function () {
    $event = Event::factory()->create();

    $seatPlan = SeatPlan::factory()->create(['event_id' => $event->id]);

    expect($seatPlan->event->id)->toBe($event->id);
    expect($seatPlan->data)->toBeArray();
    expect($seatPlan->data['blocks'])->toBeArray();
});

it('stores blocks with seats and labels as json', function () {
    $data = [
        'blocks' => [
            [
                'id' => 'orchestra',
                'title' => 'Orchestra',
                'color' => '#2c3e50',
                'seats' => [
                    ['id' => 1, 'title' => 'A1', 'x' => 0, 'y' => 0, 'salable' => true],
                    ['id' => 2, 'title' => 'A2', 'x' => 30, 'y' => 0, 'salable' => true],
                ],
                'labels' => [
                    ['title' => 'Row A', 'x' => -30, 'y' => 0],
                ],
            ],
            [
                'id' => 'balcony',
                'title' => 'Balcony',
                'color' => '#34495e',
                'seats' => [
                    ['id' => 3, 'title' => 'B1', 'x' => 0, 'y' => 0, 'salable' => true],
                ],
                'labels' => [],
            ],
        ],
    ];

    $seatPlan = SeatPlan::factory()->create(['data' => $data]);

    $seatPlan->refresh();

    expect($seatPlan->data['blocks'])->toHaveCount(2);
    expect($seatPlan->data['blocks'][0]['id'])->toBe('orchestra');
    expect($seatPlan->data['blocks'][0]['seats'])->toHaveCount(2);
    expect($seatPlan->data['blocks'][0]['labels'])->toHaveCount(1);
    expect($seatPlan->data['blocks'][1]['id'])->toBe('balcony');
});

it('stores detailed seat properties', function () {
    $data = [
        'blocks' => [
            [
                'id' => 'main',
                'title' => 'Main',
                'color' => '#4CAF50',
                'seats' => [
                    [
                        'id' => 101,
                        'title' => 'A1',
                        'x' => 100,
                        'y' => 200,
                        'salable' => true,
                        'selected' => false,
                        'note' => 'Wheelchair accessible',
                        'color' => '#4CAF50',
                        'custom_data' => [
                            'price' => 50.00,
                            'category' => 'premium',
                        ],
                    ],
                ],
                'labels' => [],
            ],
        ],
    ];

    $seatPlan = SeatPlan::factory()->create(['data' => $data]);

    $seatPlan->refresh();

    $seat = $seatPlan->data['blocks'][0]['seats'][0];
    expect($seat['id'])->toBe(101);
    expect($seat['note'])->toBe('Wheelchair accessible');
    expect($seat['custom_data']['category'])->toBe('premium');
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

it('defaults to empty blocks when created without data', function () {
    $seatPlan = SeatPlan::factory()->empty()->create();

    expect($seatPlan->data)->toBe(['blocks' => []]);
});
