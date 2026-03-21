<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Ticketing\Models\TicketType;
use Illuminate\Support\Facades\DB;

class UpdateTicketType
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(TicketType $ticketType, array $attributes): TicketType
    {
        return DB::transaction(function () use ($ticketType, $attributes): TicketType {
            if ($ticketType->is_locked) {
                unset(
                    $attributes['price'],
                    $attributes['seats_per_ticket'],
                    $attributes['is_row_ticket'],
                    $attributes['is_seatable'],
                    $attributes['event_id'],
                );
            }

            $ticketType->fill($attributes)->save();

            return $ticketType;
        });
    }
}
