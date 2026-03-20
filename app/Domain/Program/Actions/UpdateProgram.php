<?php

namespace App\Domain\Program\Actions;

use App\Domain\Program\Models\Program;
use App\Domain\Program\Models\TimeSlot;
use Illuminate\Support\Facades\DB;

class UpdateProgram
{
    /**
     * @param  array{name?: string, description?: string|null, visibility?: string, sort_order?: int}  $attributes
     * @param  array<int, array{id?: int, name: string, description?: string|null, starts_at: string, visibility: string}>  $timeSlots
     */
    public function execute(Program $program, array $attributes, array $timeSlots = []): void
    {
        DB::transaction(function () use ($program, $attributes, $timeSlots): void {
            $program->fill($attributes)->save();

            $incomingIds = collect($timeSlots)->pluck('id')->filter()->all();
            $program->timeSlots()->whereNotIn('id', $incomingIds)->delete();

            foreach ($timeSlots as $index => $slot) {
                if (isset($slot['id'])) {
                    TimeSlot::where('id', $slot['id'])->update([
                        'name' => $slot['name'],
                        'description' => $slot['description'] ?? null,
                        'starts_at' => $slot['starts_at'],
                        'visibility' => $slot['visibility'],
                        'sort_order' => $index,
                    ]);
                } else {
                    TimeSlot::create([
                        'program_id' => $program->id,
                        'name' => $slot['name'],
                        'description' => $slot['description'] ?? null,
                        'starts_at' => $slot['starts_at'],
                        'visibility' => $slot['visibility'],
                        'sort_order' => $index,
                    ]);
                }
            }
        });
    }
}
