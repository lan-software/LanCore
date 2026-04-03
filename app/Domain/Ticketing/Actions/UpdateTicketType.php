<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Ticketing\Models\TicketType;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SSS.md CAP-TKT-001, CAP-TKT-010
 * @see docs/mil-std-498/SRS.md TKT-F-001, TKT-F-011
 */
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
                    $attributes['seats_per_user'],
                    $attributes['max_users_per_ticket'],
                    $attributes['check_in_mode'],
                    $attributes['is_seatable'],
                    $attributes['event_id'],
                );
            }

            $ticketType->fill($attributes)->save();

            return $ticketType;
        });
    }
}
