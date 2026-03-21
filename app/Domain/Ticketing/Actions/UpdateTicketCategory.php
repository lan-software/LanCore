<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Ticketing\Models\TicketCategory;
use Illuminate\Support\Facades\DB;

class UpdateTicketCategory
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(TicketCategory $ticketCategory, array $attributes): TicketCategory
    {
        return DB::transaction(function () use ($ticketCategory, $attributes): TicketCategory {
            $ticketCategory->fill($attributes)->save();

            return $ticketCategory;
        });
    }
}
