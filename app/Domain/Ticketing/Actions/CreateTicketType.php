<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Ticketing\Models\TicketType;
use Illuminate\Support\Facades\DB;

class CreateTicketType
{
    /**
     * @param  array{name: string, description?: string|null, price: int, quota: int, seats_per_ticket: int, is_row_ticket: bool, is_seatable: bool, is_hidden: bool, purchase_from?: string|null, purchase_until?: string|null, event_id: int, ticket_category_id?: int|null, ticket_group_id?: int|null}  $attributes
     */
    public function execute(array $attributes): TicketType
    {
        return DB::transaction(fn (): TicketType => TicketType::create($attributes));
    }
}
