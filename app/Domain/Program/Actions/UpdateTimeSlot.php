<?php

namespace App\Domain\Program\Actions;

use App\Domain\Program\Models\TimeSlot;

/**
 * @see docs/mil-std-498/SSS.md CAP-PRG-001
 * @see docs/mil-std-498/SRS.md PRG-F-003
 */
class UpdateTimeSlot
{
    /**
     * @param  array{name?: string, description?: string|null, starts_at?: string, visibility?: string, sort_order?: int}  $attributes
     * @param  int[]|null  $sponsorIds
     */
    public function execute(TimeSlot $timeSlot, array $attributes, ?array $sponsorIds = null): void
    {
        $timeSlot->fill($attributes)->save();

        if ($sponsorIds !== null) {
            $timeSlot->sponsors()->sync($sponsorIds);
        }
    }
}
