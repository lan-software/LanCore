<?php

namespace App\Domain\Program\Actions;

use App\Domain\Program\Models\TimeSlot;

/**
 * @see docs/mil-std-498/SRS.md PRG-F-003
 */
class DeleteTimeSlot
{
    public function execute(TimeSlot $timeSlot): void
    {
        $timeSlot->delete();
    }
}
