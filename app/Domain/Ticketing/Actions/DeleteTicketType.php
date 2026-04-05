<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Ticketing\Models\TicketType;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SRS.md TKT-F-001
 */
class DeleteTicketType
{
    public function execute(TicketType $ticketType): void
    {
        DB::transaction(fn () => $ticketType->delete());
    }
}
