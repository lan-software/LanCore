<?php

namespace App\Domain\CompetitionSchedule\Services;

use App\Domain\Api\Clients\LanBracketsClient;
use App\Domain\Competition\Enums\StageType;
use App\Domain\Competition\Exceptions\LanBracketsRequestException;
use App\Domain\Competition\Models\Competition;
use App\Domain\CompetitionSchedule\Models\CompetitionRoundSchedule;
use App\Domain\CompetitionSchedule\Models\CompetitionStageSchedule;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pulls stage metadata from LanBrackets and upserts CompetitionStageSchedule
 * rows for the given competition. Preserves organizer-edited fields
 * (starts_at, notes, overridden duration). Computes default durations via
 * DurationEstimator whenever the input hash changes.
 *
 * For each synced stage the synchronizer also pulls matches and derives
 * CompetitionRoundSchedule rows grouped by `round_number`. Round-level
 * overrides (organizer-set starts_at, notes, or duration_overridden=true)
 * are preserved across re-syncs.
 *
 * @see docs/mil-std-498/SRS.md COMP-SCH-002
 * @see docs/mil-std-498/SRS.md COMP-RND-002
 */
class StageScheduleSynchronizer
{
    public function __construct(
        private readonly LanBracketsClient $client,
        private readonly DurationEstimator $estimator,
        private readonly RoundDurationDefaults $roundDefaults,
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
                $schedule = CompetitionStageSchedule::query()->create([
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

            $this->syncRoundsForStage($competition, $schedule);

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

    /**
     * Pull matches for the stage, group by `round_number`, and upsert one
     * CompetitionRoundSchedule row per distinct round. Preserves overrides:
     * if a round already has `duration_overridden=true` or a non-null
     * `starts_at`, its duration/reserve/starts_at/notes are left alone.
     *
     * Default durations come from {@see RoundDurationDefaults}, then
     * scaled uniformly so the rounds' total wall-clock matches the parent
     * stage's `estimated_duration_minutes + reserve_buffer_minutes`.
     *
     * @see docs/mil-std-498/SRS.md COMP-RND-002
     */
    private function syncRoundsForStage(Competition $competition, CompetitionStageSchedule $stageSchedule): void
    {
        try {
            $matches = $this->client->getMatches(
                (string) $competition->lanbrackets_id,
                $stageSchedule->lanbrackets_stage_id,
            );
        } catch (LanBracketsRequestException $e) {
            Log::warning('StageScheduleSynchronizer: failed to fetch matches.', [
                'competition_id' => $competition->id,
                'stage_schedule_id' => $stageSchedule->id,
                'error' => $e->getMessage(),
            ]);

            return;
        } catch (Throwable $e) {
            Log::warning('StageScheduleSynchronizer: unexpected error fetching matches.', [
                'competition_id' => $competition->id,
                'stage_schedule_id' => $stageSchedule->id,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        $roundNumbers = [];
        foreach ($matches as $match) {
            $rn = isset($match['round_number']) ? (int) $match['round_number'] : null;
            if ($rn !== null && $rn > 0) {
                $roundNumbers[$rn] = true;
            }
        }
        if ($roundNumbers === []) {
            return;
        }
        $sorted = array_keys($roundNumbers);
        sort($sorted);
        $totalRounds = count($sorted);

        $stageType = StageType::tryFrom($stageSchedule->stage_type) ?? StageType::SingleElimination;
        $stageBudget = $stageSchedule->estimated_duration_minutes + $stageSchedule->reserve_buffer_minutes;

        $defaults = [];
        foreach ($sorted as $position => $roundNumber) {
            $defaults[] = $this->roundDefaults->forRound($stageType, $position + 1, $totalRounds);
        }
        $defaults = $this->roundDefaults->scaleToStageMinutes($defaults, $stageBudget);

        $seen = [];
        foreach ($sorted as $position => $roundNumber) {
            $seen[] = $roundNumber;
            $default = $defaults[$position];

            $round = CompetitionRoundSchedule::query()
                ->where('stage_schedule_id', $stageSchedule->id)
                ->where('lanbrackets_round_number', $roundNumber)
                ->first();

            $defaultLabel = $this->defaultRoundLabel($stageType, $position + 1, $totalRounds);

            if ($round === null) {
                CompetitionRoundSchedule::query()->create([
                    'stage_schedule_id' => $stageSchedule->id,
                    'lanbrackets_round_number' => $roundNumber,
                    'sequence' => $position + 1,
                    'label' => $defaultLabel,
                    'estimated_duration_minutes' => $default['duration'],
                    'reserve_buffer_minutes' => $default['reserve'],
                    'duration_overridden' => false,
                ]);
            } else {
                $updates = [
                    'sequence' => $position + 1,
                ];
                if ($round->label === null) {
                    $updates['label'] = $defaultLabel;
                }
                if (! $round->duration_overridden) {
                    $updates['estimated_duration_minutes'] = $default['duration'];
                    $updates['reserve_buffer_minutes'] = $default['reserve'];
                }
                $round->update($updates);
            }
        }

        CompetitionRoundSchedule::query()
            ->where('stage_schedule_id', $stageSchedule->id)
            ->whereNotIn('lanbrackets_round_number', $seen)
            ->delete();
    }

    private function defaultRoundLabel(StageType $stageType, int $position, int $totalRounds): string
    {
        if (in_array($stageType, [StageType::SingleElimination, StageType::DoubleElimination], true)) {
            $fromEnd = $totalRounds - $position;

            return match (true) {
                $stageType === StageType::DoubleElimination && $position > $totalRounds => 'Grand Final',
                $fromEnd === 0 => 'Final',
                $fromEnd === 1 => 'Semifinal',
                $fromEnd === 2 => 'Quarterfinal',
                $position === 1 => 'First Matches',
                default => 'Round of '.(2 ** ($fromEnd + 1)),
            };
        }

        return "Round {$position}";
    }
}
