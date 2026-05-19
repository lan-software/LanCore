<?php

namespace App\Domain\CompetitionSchedule\Services;

use App\Domain\Api\Clients\LanBracketsClient;
use App\Domain\Competition\Exceptions\LanBracketsRequestException;
use App\Domain\Competition\Models\Competition;
use App\Domain\CompetitionSchedule\Models\CompetitionStageSchedule;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pulls stage metadata from LanBrackets and upserts CompetitionStageSchedule
 * rows for the given competition. Preserves organizer-edited fields
 * (starts_at, notes, overridden duration). Computes default durations via
 * DurationEstimator whenever the input hash changes.
 *
 * @see docs/mil-std-498/SRS.md COMP-SCH-002
 */
class StageScheduleSynchronizer
{
    public function __construct(
        private readonly LanBracketsClient $client,
        private readonly DurationEstimator $estimator,
    ) {}

    /**
     * @return int Number of stage schedule rows upserted (insert or update).
     */
    public function syncCompetition(Competition $competition): int
    {
        if (! $competition->isSyncedToLanBrackets()) {
            return 0;
        }

        try {
            $stages = $this->client->getStages((string) $competition->lanbrackets_id);
        } catch (LanBracketsRequestException $e) {
            Log::warning('StageScheduleSynchronizer: failed to fetch stages.', [
                'competition_id' => $competition->id,
                'error' => $e->getMessage(),
            ]);

            return 0;
        } catch (Throwable $e) {
            Log::warning('StageScheduleSynchronizer: unexpected error.', [
                'competition_id' => $competition->id,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }

        $seenStageIds = [];
        $touched = 0;
        $sequence = 1;

        foreach ($stages as $stage) {
            $stageId = isset($stage['id']) ? (string) $stage['id'] : null;
            if ($stageId === null || $stageId === '') {
                continue;
            }
            $seenStageIds[] = $stageId;

            $schedule = CompetitionStageSchedule::query()
                ->where('competition_id', $competition->id)
                ->where('lanbrackets_stage_id', $stageId)
                ->first();

            $stageName = (string) ($stage['name'] ?? "Stage {$sequence}");
            $stageType = (string) ($stage['stage_type'] ?? $stage['type'] ?? $competition->stage_type?->value ?? 'single_elimination');

            $computed = $this->estimator->estimate($competition, $stage);

            if ($schedule === null) {
                CompetitionStageSchedule::query()->create([
                    'competition_id' => $competition->id,
                    'lanbrackets_stage_id' => $stageId,
                    'stage_name' => $stageName,
                    'stage_type' => $stageType,
                    'sequence' => (int) ($stage['order'] ?? $sequence),
                    'estimated_duration_minutes' => $computed->minutes,
                    'reserve_buffer_minutes' => $this->defaultReserveMinutes($computed->minutes),
                    'duration_overridden' => false,
                    'computed_inputs_hash' => $computed->hash,
                ]);
                $touched++;
            } else {
                $updates = [
                    'stage_name' => $stageName,
                    'stage_type' => $stageType,
                    'sequence' => (int) ($stage['order'] ?? $schedule->sequence),
                ];

                if (! $schedule->duration_overridden) {
                    $updates['estimated_duration_minutes'] = $computed->minutes;
                    $updates['computed_inputs_hash'] = $computed->hash;
                } elseif ($schedule->computed_inputs_hash !== $computed->hash) {
                    // record the new hash so callers can detect staleness vs current LB state
                    $updates['computed_inputs_hash'] = $computed->hash;
                }

                $schedule->update($updates);
                $touched++;
            }

            $sequence++;
        }

        if ($seenStageIds !== []) {
            CompetitionStageSchedule::query()
                ->where('competition_id', $competition->id)
                ->whereNotIn('lanbrackets_stage_id', $seenStageIds)
                ->delete();
        }

        return $touched;
    }

    private function defaultReserveMinutes(int $estimatedMinutes): int
    {
        // 20% reserve, capped to a sensible band [10, 60] minutes
        return max(10, min(60, (int) round($estimatedMinutes * 0.2)));
    }
}
