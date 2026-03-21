<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Ticketing\Models\TicketType;
use Illuminate\Support\Facades\DB;

class DeleteTicketType
{
    public function execute(TicketType $ticketType): void
    {
        DB::transaction(fn () => $ticketType->delete());
    }
}
