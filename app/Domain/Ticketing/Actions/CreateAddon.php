<?php

namespace App\Domain\Ticketing\Actions;

use App\Domain\Ticketing\Models\Addon;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SSS.md CAP-TKT-004
 * @see docs/mil-std-498/SRS.md TKT-F-007
 */
class CreateAddon
{
    /**
     * @param  array{name: string, description?: string|null, price: int, quota?: int|null, seats_consumed: int, requires_ticket?: bool, is_hidden?: bool, event_id: int}  $attributes
     */
    public function execute(array $attributes): Addon
    {
        return DB::transaction(fn (): Addon => Addon::create($attributes));
    }
}
