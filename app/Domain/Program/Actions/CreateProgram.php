<?php

namespace App\Domain\Program\Actions;

use App\Domain\Program\Models\Program;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SSS.md CAP-PRG-001
 * @see docs/mil-std-498/SRS.md PRG-F-001
 */
class CreateProgram
{
    public function __construct(private readonly CreateTimeSlot $createTimeSlot) {}

    /**
     * @param  array{name: string, description?: string|null, visibility: string, event_id: int, sort_order?: int}  $attributes
     * @param  array<int, array{name: string, description?: string|null, starts_at: string, visibility: string}>  $timeSlots
     */
    public function execute(array $attributes, array $timeSlots = []): Program
    {
        return DB::transaction(function () use ($attributes, $timeSlots): Program {
            $program = Program::create($attributes);

            foreach ($timeSlots as $index => $slot) {
                $this->createTimeSlot->execute($program, [
                    ...$slot,
                    'sort_order' => $index,
                ]);
            }

            return $program;
        });
    }
}
