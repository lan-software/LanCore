<?php

namespace App\Domain\Program\Actions;

use App\Domain\Program\Models\Program;
use App\Domain\Program\Models\TimeSlot;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SSS.md CAP-PRG-001
 * @see docs/mil-std-498/SRS.md PRG-F-001, PRG-F-006
 */
class UpdateProgram
{
    /**
     * @param  array{name?: string, description?: string|null, visibility?: string, sort_order?: int}  $attributes
     * @param  array<int, array{id?: int, name: string, description?: string|null, starts_at: string, visibility: string, sponsor_ids?: int[]}>  $timeSlots
     * @param  int[]|null  $sponsorIds
     */
    public function execute(Program $program, array $attributes, array $timeSlots = [], ?array $sponsorIds = null): void
    {
        DB::transaction(function () use ($program, $attributes, $timeSlots, $sponsorIds): void {
            $program->fill($attributes)->save();

            if ($sponsorIds !== null) {
                $program->sponsors()->sync($sponsorIds);
            }

            $incomingIds = collect($timeSlots)->pluck('id')->filter()->all();
            $program->timeSlots()->whereNotIn('id', $incomingIds)->delete();

            foreach ($timeSlots as $index => $slot) {
                $slotSponsorIds = $slot['sponsor_ids'] ?? null;

                if (isset($slot['id'])) {
                    TimeSlot::where('id', $slot['id'])->update([
                        'name' => $slot['name'],
                        'description' => $slot['description'] ?? null,
                        'starts_at' => $slot['starts_at'],
                        'visibility' => $slot['visibility'],
                        'sort_order' => $index,
                    ]);

                    if ($slotSponsorIds !== null) {
                        TimeSlot::find($slot['id'])->sponsors()->sync($slotSponsorIds);
                    }
                } else {
                    $newSlot = TimeSlot::create([
                        'program_id' => $program->id,
                        'name' => $slot['name'],
                        'description' => $slot['description'] ?? null,
                        'starts_at' => $slot['starts_at'],
                        'visibility' => $slot['visibility'],
                        'sort_order' => $index,
                    ]);

                    if ($slotSponsorIds !== null) {
                        $newSlot->sponsors()->sync($slotSponsorIds);
                    }
                }
            }
        });
    }
}
