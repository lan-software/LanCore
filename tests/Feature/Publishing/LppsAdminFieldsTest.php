<?php

use App\Domain\Event\Actions\CreateEvent;
use App\Domain\Event\Enums\AttendanceMode;
use App\Domain\Venue\Actions\CreateVenue;

/**
 * @see docs/mil-std-498/STD.md TC-PUB-007, TC-PUB-008
 */
it('persists LPPS event fields through CreateEvent', function () {
    $event = app(CreateEvent::class)->execute([
        'name' => 'Field Test LAN',
        'start_date' => '2026-07-15 16:00:00',
        'end_date' => '2026-07-16 16:00:00',
        'attendance_mode' => AttendanceMode::Mixed->value,
        'sleeping' => 8,
        'has_showers' => true,
        'food_policy' => 4,
        'network_connection_mbps' => 1000,
    ]);

    $event->refresh();

    expect($event->attendance_mode)->toBe(AttendanceMode::Mixed);
    expect($event->sleeping)->toBe(8);
    expect($event->has_showers)->toBeTrue();
    expect($event->food_policy)->toBe(4);
    expect($event->network_connection_mbps)->toBe(1000);
});

it('persists and upper-cases geographic fields through CreateVenue', function () {
    $venue = app(CreateVenue::class)->execute([
        'name' => 'Geo Hall',
        'street' => 'Main St 1',
        'city' => 'Testville',
        'zip_code' => '12345',
        'country' => 'Germany',
        'latitude' => 51.5,
        'longitude' => -0.12,
        'country_code' => 'gb',
    ]);

    $venue->refresh()->load('address');

    expect($venue->address->latitude)->toBe(51.5);
    expect($venue->address->longitude)->toBe(-0.12);
    expect($venue->address->country_code)->toBe('GB');
});
