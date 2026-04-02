<?php

namespace App\Domain\Sponsoring\Actions;

use App\Domain\Sponsoring\Models\Sponsor;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SRS.md SPO-F-001, SPO-F-003, SPO-F-004
 */
class UpdateSponsor
{
    /**
     * @param  array{name?: string, description?: string|null, link?: string|null, logo?: string|null, sponsor_level_id?: int|null}  $attributes
     * @param  array<int>|null  $eventIds
     * @param  array<int>|null  $managerIds
     */
    public function execute(Sponsor $sponsor, array $attributes, ?array $eventIds = null, ?array $managerIds = null): void
    {
        DB::transaction(function () use ($sponsor, $attributes, $eventIds, $managerIds): void {
            $sponsor->fill($attributes)->save();

            if ($eventIds !== null) {
                $sponsor->events()->sync($eventIds);
            }

            if ($managerIds !== null) {
                $sponsor->managers()->sync($managerIds);
            }
        });
    }
}
