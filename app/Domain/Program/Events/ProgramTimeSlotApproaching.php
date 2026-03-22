<?php

namespace App\Domain\Program\Events;

use App\Domain\Program\Models\TimeSlot;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProgramTimeSlotApproaching
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly TimeSlot $timeSlot) {}
}
