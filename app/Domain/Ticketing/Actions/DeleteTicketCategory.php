<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Ticketing\Models\TicketCategory;
use Illuminate\Support\Facades\DB;

class DeleteTicketCategory
{
    public function execute(TicketCategory $ticketCategory): void
    {
        DB::transaction(fn () => $ticketCategory->delete());
    }
}
