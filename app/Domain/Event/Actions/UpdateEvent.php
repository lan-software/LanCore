<?php

namespace App\Domain\Event\Actions;

use App\Domain\Event\Models\Event;
use Illuminate\Support\Facades\DB;

class UpdateEvent
{
    /**
     * @param  array{name?: string, description?: string|null, start_date?: string, end_date?: string, banner_image?: string|null, venue_id?: int|null}  $attributes
     */
    public function execute(Event $event, array $attributes): void
    {
        DB::transaction(function () use ($event, $attributes): void {
            $event->fill($attributes)->save();
        });
    }
}
