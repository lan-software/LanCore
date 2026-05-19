<?php

namespace App\Domain\CompetitionSchedule\Http\Controllers;

use App\Domain\CompetitionSchedule\Events\StageScheduleUpdated;
use App\Domain\CompetitionSchedule\Http\Requests\UpdateStageScheduleRequest;
use App\Domain\CompetitionSchedule\Models\CompetitionStageSchedule;
use App\Domain\CompetitionSchedule\Services\DurationEstimator;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

/**
 * @see docs/mil-std-498/SRS.md COMP-SCH-006
 */
class StageScheduleController extends Controller
{
    public function __construct(
        private readonly DurationEstimator $estimator,
    ) {}

    public function update(UpdateStageScheduleRequest $request, CompetitionStageSchedule $schedule): RedirectResponse
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

        if ($request->boolean('reset_to_computed')) {
            $competition = $schedule->competition()->with('game')->first();
            if ($competition !== null) {
                $computed = $this->estimator->estimate($competition, [
                    'stage_type' => $schedule->stage_type,
                ]);
                $updates['estimated_duration_minutes'] = $computed->minutes;
                $updates['computed_inputs_hash'] = $computed->hash;
                $updates['duration_overridden'] = false;
            }
        } elseif ($request->has('estimated_duration_minutes')) {
            $updates['estimated_duration_minutes'] = (int) $validated['estimated_duration_minutes'];
            $updates['duration_overridden'] = true;
        }

        $schedule->update($updates);

        $competition = $schedule->competition;
        StageScheduleUpdated::dispatch($competition?->event_id, (string) $schedule->competition_id);

        return back();
    }
}
