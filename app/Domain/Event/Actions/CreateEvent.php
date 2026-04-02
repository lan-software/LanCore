<?php

namespace App\Domain\Event\Actions;

use App\Domain\Event\Enums\EventStatus;
use App\Domain\Event\Models\Event;
use Illuminate\Support\Facades\DB;

class CreateEvent
{
    /**
     * @param  array{name: string, description?: string|null, start_date: string, end_date: string, banner_images?: string[], venue_id?: int|null, seat_capacity?: int|null}  $attributes
     *
     * @see docs/mil-std-498/SSS.md CAP-EVT-001
     * @see docs/mil-std-498/SRS.md EVT-F-001, EVT-F-002
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
        ]));
    }
}
