<?php

namespace App\Domain\CompetitionSchedule\Http\Controllers;

use App\Domain\CompetitionSchedule\Events\RoundScheduleUpdated;
use App\Domain\CompetitionSchedule\Http\Requests\UpdateRoundScheduleRequest;
use App\Domain\CompetitionSchedule\Models\CompetitionRoundSchedule;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

/**
 * @see docs/mil-std-498/SRS.md COMP-RND-004
 */
class RoundScheduleController extends Controller
{
    public function update(UpdateRoundScheduleRequest $request, CompetitionRoundSchedule $schedule): RedirectResponse
    {
        $validated = $request->validated();

        $updates = [];

        if ($request->has('starts_at')) {
            $updates['starts_at'] = $validated['starts_at'] ?? null;
        }
        if ($request->has('reserve_buffer_minutes')) {
            $updates['reserve_buffer_minutes'] = (int) $validated['reserve_buffer_minutes'];
        }
        if ($request->has('notes')) {
            $updates['notes'] = $validated['notes'] ?? null;
        }
        if ($request->has('label')) {
            $updates['label'] = $validated['label'] ?? null;
        }

        if ($request->boolean('reset_to_computed')) {
            $updates['duration_overridden'] = false;
        } elseif ($request->has('estimated_duration_minutes')) {
            $updates['estimated_duration_minutes'] = (int) $validated['estimated_duration_minutes'];
            $updates['duration_overridden'] = true;
        }

        $schedule->update($updates);

        $stageSchedule = $schedule->stageSchedule()->with('competition')->first();
        $competition = $stageSchedule?->competition;

        RoundScheduleUpdated::dispatch(
            $competition?->event_id,
            (string) ($stageSchedule?->competition_id ?? ''),
            (string) $schedule->stage_schedule_id,
            (string) $schedule->id,
        );

        return back();
    }
}
