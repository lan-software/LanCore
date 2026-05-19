<?php

namespace App\Console\Commands\Competition;

use App\Domain\Competition\Models\Competition;
use App\Domain\CompetitionSchedule\Services\StageScheduleSynchronizer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Sync CompetitionStageSchedule rows from LanBrackets for every
 * LanBrackets-linked competition (or a filtered subset).
 *
 *   php artisan competitions:sync-stages
 *   php artisan competitions:sync-stages --event=01J...
 *   php artisan competitions:sync-stages --competition=01J...
 *
 * @see docs/mil-std-498/SRS.md COMP-SCH-002
 */
#[Signature('competitions:sync-stages {--event= : Limit to competitions of this event} {--competition= : Sync a single competition}')]
#[Description('Sync competition stage schedules from LanBrackets into LanCore')]
class SyncStageSchedulesCommand extends Command
{
    public function handle(StageScheduleSynchronizer $synchronizer): int
    {
        $query = Competition::query()->whereNotNull('lanbrackets_id');

        if ($eventId = $this->option('event')) {
            $query->where('event_id', $eventId);
        }
        if ($competitionId = $this->option('competition')) {
            $query->where('id', $competitionId);
        }

        $competitions = $query->get();

        if ($competitions->isEmpty()) {
            $this->components->warn('No LanBrackets-linked competitions matched.');

            return self::SUCCESS;
        }

        $rows = [];
        foreach ($competitions as $competition) {
            $touched = $synchronizer->syncCompetition($competition);
            $rows[] = [
                'competition' => $competition->name,
                'id' => $competition->id,
                'stages_synced' => $touched,
            ];
        }

        $this->table(['competition', 'id', 'stages_synced'], $rows);

        return self::SUCCESS;
    }
}
