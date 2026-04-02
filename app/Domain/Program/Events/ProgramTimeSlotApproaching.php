<?php

namespace App\Domain\Program\Events;

use App\Domain\Program\Models\TimeSlot;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * @see docs/mil-std-498/SSS.md CAP-PRG-004
 * @see docs/mil-std-498/SRS.md PRG-F-004
 */
class ProgramTimeSlotApproaching
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly TimeSlot $timeSlot) {}
}
