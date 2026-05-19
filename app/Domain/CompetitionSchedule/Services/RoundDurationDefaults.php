<?php

namespace App\Domain\CompetitionSchedule\Services;

use App\Domain\Competition\Enums\StageType;

/**
 * Default duration and reserve for a round, keyed by the parent stage's type
 * and the round's position within the stage (1-indexed, where 1 is the first
 * round and N is the final).
 *
 * The defaults follow the slack-budget table in the OCD: SE/DE compress early
 * rounds (many matches in parallel, short reserve) and expand the tail
 * (finals, grand final). RR/Swiss are roughly flat. Race heats and timeraces
 * keep a minimal buffer because the format absorbs slack naturally.
 *
 * Callers can scale all values uniformly to fit a stage-level duration
 * budget via {@see self::scaleToStageMinutes()}.
 *
 * @see docs/mil-std-498/SRS.md COMP-RND-003
 */
class RoundDurationDefaults
{
    /**
     * @return array{duration: int, reserve: int}
     */
    public function forRound(StageType $stageType, int $position, int $totalRounds): array
    {
        return match ($stageType) {
            StageType::SingleElimination,
            StageType::DoubleElimination => $this->eliminationDefaults($position, $totalRounds),
            StageType::Swiss => ['duration' => 35, 'reserve' => 10],
            StageType::RoundRobin => ['duration' => 30, 'reserve' => 10],
            StageType::GroupStage => ['duration' => 30, 'reserve' => 10],
            StageType::RaceHeat => ['duration' => 20, 'reserve' => 5],
            StageType::FinalStage => ['duration' => 60, 'reserve' => 20],
        };
    }

    /**
     * Scale a list of round defaults uniformly so that the total wall-clock
     * (sum of duration + reserve) matches `$stageMinutes`. Returns the same
     * shape, with each entry's duration/reserve scaled proportionally.
     *
     * @param  array<int, array{duration: int, reserve: int}>  $rounds
     * @return array<int, array{duration: int, reserve: int}>
     */
    public function scaleToStageMinutes(array $rounds, int $stageMinutes): array
    {
        $current = 0;
        foreach ($rounds as $r) {
            $current += $r['duration'] + $r['reserve'];
        }
        if ($current <= 0 || $stageMinutes <= 0) {
            return $rounds;
        }
        $factor = $stageMinutes / $current;

        return array_map(
            fn (array $r): array => [
                'duration' => max(5, (int) round($r['duration'] * $factor)),
                'reserve' => max(0, (int) round($r['reserve'] * $factor)),
            ],
            $rounds,
        );
    }

    /**
     * @return array{duration: int, reserve: int}
     */
    private function eliminationDefaults(int $position, int $totalRounds): array
    {
        $roundsFromEnd = $totalRounds - $position;

        return match (true) {
            $roundsFromEnd <= 0 => ['duration' => 60, 'reserve' => 20], // final (or GF)
            $roundsFromEnd === 1 => ['duration' => 50, 'reserve' => 15], // semifinal
            $roundsFromEnd === 2 => ['duration' => 40, 'reserve' => 10], // quarterfinal
            default => ['duration' => 30, 'reserve' => 5],               // earlier rounds (R1, R16, etc.)
        };
    }
}
