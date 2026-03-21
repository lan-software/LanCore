<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Ticketing\Models\TicketCategory;
use Illuminate\Support\Facades\DB;

class CreateTicketCategory
{
    /**
     * @param  array{name: string, description?: string|null, sort_order?: int}  $attributes
     */
    public function execute(array $attributes): TicketCategory
    {
        return DB::transaction(fn (): TicketCategory => TicketCategory::create($attributes));
    }
}
