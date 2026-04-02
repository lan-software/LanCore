<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Ticketing\Models\TicketCategory;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SRS.md TKT-F-002
 */
class DeleteTicketCategory
{
    public function execute(TicketCategory $ticketCategory): void
    {
        DB::transaction(fn () => $ticketCategory->delete());
    }
}
