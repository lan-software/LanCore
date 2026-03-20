<?php

namespace App\Domain\Program\Actions;

use App\Domain\Program\Models\Program;
use App\Domain\Program\Models\TimeSlot;
use Illuminate\Support\Facades\DB;

class CreateProgram
{
    /**
     * @param  array{name: string, description?: string|null, visibility: string, event_id: int, sort_order?: int}  $attributes
     * @param  array<int, array{name: string, description?: string|null, starts_at: string, visibility: string}>  $timeSlots
     */
    public function execute(array $attributes, array $timeSlots = []): Program
    {
        return DB::transaction(function () use ($attributes, $timeSlots): Program {
            $program = Program::create($attributes);

            foreach ($timeSlots as $index => $slot) {
                TimeSlot::create([
                    'program_id' => $program->id,
                    'name' => $slot['name'],
                    'description' => $slot['description'] ?? null,
                    'starts_at' => $slot['starts_at'],
                    'visibility' => $slot['visibility'] ?? $program->visibility->value,
                    'sort_order' => $index,
                ]);
            }

            return $program;
        });
    }
}
