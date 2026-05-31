<?php

namespace App\Domain\Event\Actions;

use App\Domain\Event\Enums\AttendanceMode;
use App\Domain\Event\Enums\EventStatus;
use App\Domain\Event\Enums\EventSyndicationStatus;
use App\Domain\Event\Models\Event;
use Illuminate\Support\Facades\DB;

class CreateEvent
{
    /**
     * @param  array{name: string, description?: string|null, start_date: string, end_date: string, banner_images?: string[], venue_id?: int|null, seat_capacity?: int|null, attendance_mode?: int|null, syndication_status?: string|null, previous_start_date?: string|null, has_showers?: bool|null, sleeping?: int|null, alcohol_policy?: int|null, smoking_policy?: int|null, age_policy?: int|null, food_policy?: int|null, network_connection_mbps?: int|null, internet_connection_mbps?: int|null, wifi_connection_mbps?: int|null}  $attributes
     *
     * @see docs/mil-std-498/SSS.md CAP-EVT-001, CAP-PUB-004
     * @see docs/mil-std-498/SRS.md EVT-F-001, EVT-F-002, EVT-F-013
     */
    public function execute(array $attributes): Event
    {
        return DB::transaction(fn (): Event => Event::create([
            'name' => $attributes['name'],
            'description' => $attributes['description'] ?? null,
            'start_date' => $attributes['start_date'],
            'end_date' => $attributes['end_date'],
            'banner_images' => $attributes['banner_images'] ?? [],
            'status' => EventStatus::Draft,
            'venue_id' => $attributes['venue_id'] ?? null,
            'seat_capacity' => $attributes['seat_capacity'] ?? null,
            'attendance_mode' => $attributes['attendance_mode'] ?? AttendanceMode::Offline,
            'syndication_status' => $attributes['syndication_status'] ?? EventSyndicationStatus::Scheduled,
            'previous_start_date' => $attributes['previous_start_date'] ?? null,
            'has_showers' => $attributes['has_showers'] ?? null,
            'sleeping' => $attributes['sleeping'] ?? 0,
            'alcohol_policy' => $attributes['alcohol_policy'] ?? 0,
            'smoking_policy' => $attributes['smoking_policy'] ?? 0,
            'age_policy' => $attributes['age_policy'] ?? 0,
            'food_policy' => $attributes['food_policy'] ?? 0,
            'network_connection_mbps' => $attributes['network_connection_mbps'] ?? null,
            'internet_connection_mbps' => $attributes['internet_connection_mbps'] ?? null,
            'wifi_connection_mbps' => $attributes['wifi_connection_mbps'] ?? null,
        ]));
    }
}
