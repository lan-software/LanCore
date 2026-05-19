<?php

namespace App\Domain\CompetitionSchedule\Jobs;

use App\Domain\Competition\Models\Competition;
use App\Domain\CompetitionSchedule\Events\StageScheduleUpdated;
use App\Domain\CompetitionSchedule\Services\StageScheduleSynchronizer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Queue-side sync from LanBrackets stage state into LanCore
 * CompetitionStageSchedule rows. Idempotent.
 *
 * @see docs/mil-std-498/SRS.md COMP-SCH-002
 */
class SyncCompetitionStagesJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(public readonly string $competitionId) {}

    public function handle(StageScheduleSynchronizer $synchronizer): void
    {
        $competition = Competition::find($this->competitionId);

        if ($competition === null) {
            return;
        }

        $touched = $synchronizer->syncCompetition($competition);

        if ($touched > 0) {
            StageScheduleUpdated::dispatch($competition->event_id, $competition->id);
        }
    }
}
