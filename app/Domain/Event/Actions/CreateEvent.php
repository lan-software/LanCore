<?php

namespace App\Domain\Event\Actions;

use App\Domain\Event\Enums\EventStatus;
use App\Domain\Event\Models\Event;
use Illuminate\Support\Facades\DB;

class CreateEvent
{
    /**
     * @param  array{name: string, description?: string|null, start_date: string, end_date: string, banner_image?: string|null, venue_id?: int|null}  $attributes
     */
    public function execute(array $attributes): Event
    {
        return DB::transaction(fn (): Event => Event::create([
            'name' => $attributes['name'],
            'description' => $attributes['description'] ?? null,
            'start_date' => $attributes['start_date'],
            'end_date' => $attributes['end_date'],
            'banner_image' => $attributes['banner_image'] ?? null,
            'status' => EventStatus::Draft,
            'venue_id' => $attributes['venue_id'] ?? null,
        ]));
    }
}
