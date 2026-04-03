<?php

namespace App\Domain\Program\Actions;

use App\Domain\Program\Models\Program;
use App\Domain\Program\Models\TimeSlot;

/**
 * @see docs/mil-std-498/SSS.md CAP-PRG-001
 * @see docs/mil-std-498/SRS.md PRG-F-003
 */
class CreateTimeSlot
{
    /**
     * @param  array{name: string, description?: string|null, starts_at: string, visibility?: string, sort_order?: int}  $attributes
     * @param  int[]|null  $sponsorIds
     */
    public function execute(Program $program, array $attributes, ?array $sponsorIds = null): TimeSlot
    {
        $timeSlot = TimeSlot::create([
            'program_id' => $program->id,
            'name' => $attributes['name'],
            'description' => $attributes['description'] ?? null,
            'starts_at' => $attributes['starts_at'],
            'visibility' => $attributes['visibility'] ?? $program->visibility->value,
            'sort_order' => $attributes['sort_order'] ?? ($program->timeSlots()->max('sort_order') ?? -1) + 1,
        ]);

        if ($sponsorIds !== null) {
            $timeSlot->sponsors()->sync($sponsorIds);
        }

        return $timeSlot;
    }
}
