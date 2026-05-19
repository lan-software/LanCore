<?php

namespace App\Domain\CompetitionSchedule\Services;

use App\Domain\Competition\Models\Competition;
use App\Domain\CompetitionSchedule\ValueObjects\DurationEstimate;

/**
 * Computes a default `estimated_duration_minutes` for a stage from
 * LanBrackets stage payload + the competition's game configuration.
 * Produces a stable hash so callers can detect when the underlying
 * inputs change (e.g., match count after bracket regeneration).
 *
 * @see docs/mil-std-498/SRS.md COMP-SCH-003
 */
class DurationEstimator
{
    private const DEFAULT_AVG_MATCH_MINUTES = 30;

    private const DEFAULT_AVG_STAGE_MINUTES = 60;

    /**
     * @param  array<string, mixed>  $stage  LanBrackets stage payload
     */
    public function estimate(Competition $competition, array $stage): DurationEstimate
    {
        $game = $competition->game;
        $avgMatch = (int) ($game?->avg_match_minutes ?? self::DEFAULT_AVG_MATCH_MINUTES);
        $avgStage = (int) ($game?->avg_stage_minutes ?? self::DEFAULT_AVG_STAGE_MINUTES);

        $stageType = (string) ($stage['stage_type'] ?? $stage['type'] ?? 'single_elimination');
        $matchCount = isset($stage['match_count']) ? (int) $stage['match_count'] : null;
        $teamCount = $this->resolveTeamCount($competition, $stage);
        $settings = is_array($stage['settings'] ?? null) ? $stage['settings'] : [];

        if ($matchCount !== null && $matchCount > 0) {
            $minutes = max($avgStage, $matchCount * $avgMatch);
            $strategy = 'match_count';
        } else {
            [$minutes, $strategy] = $this->estimateFromTeamCount(
                $stageType,
                $teamCount,
                $avgMatch,
                $avgStage,
                $settings,
            );
        }

        $hash = $this->hashInputs([
            'strategy' => $strategy,
            'match_count' => $matchCount,
            'team_count' => $teamCount,
            'avg_match' => $avgMatch,
            'avg_stage' => $avgStage,
            'stage_type' => $stageType,
            'total_rounds' => $settings['total_rounds'] ?? null,
        ]);

        return new DurationEstimate(minutes: $minutes, hash: $hash, strategy: $strategy);
    }

    /**
     * @param  array<string, mixed>  $stage
     */
    private function resolveTeamCount(Competition $competition, array $stage): int
    {
        if (isset($stage['team_count'])) {
            return max(2, (int) $stage['team_count']);
        }

        if (isset($stage['participant_count'])) {
            return max(2, (int) $stage['participant_count']);
        }

        $relationLoaded = $competition->relationLoaded('teams')
            ? $competition->teams->count()
            : $competition->teams()->count();

        if ($relationLoaded > 1) {
            return $relationLoaded;
        }

        return max(2, (int) ($competition->max_teams ?? 8));
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array{0:int,1:string}
     */
    private function estimateFromTeamCount(
        string $stageType,
        int $teamCount,
        int $avgMatch,
        int $avgStage,
        array $settings,
    ): array {
        $teams = max(2, $teamCount);

        return match ($stageType) {
            'single_elimination' => [$this->singleElimMinutes($teams, $avgMatch), 'team_count_single_elim'],
            'double_elimination' => [$this->doubleElimMinutes($teams, $avgMatch), 'team_count_double_elim'],
            'round_robin' => [$this->roundRobinMinutes($teams, $avgMatch), 'team_count_round_robin'],
            'swiss' => [$this->swissMinutes($teams, $avgMatch, $settings), 'team_count_swiss'],
            'group_stage', 'race_heat', 'final_stage' => [$avgStage, 'team_count_default'],
            default => [$avgStage, 'team_count_default'],
        };
    }

    private function singleElimMinutes(int $teams, int $avgMatch): int
    {
        $rounds = (int) ceil(log($teams, 2));

        return max($avgMatch, $rounds * $avgMatch);
    }

    private function doubleElimMinutes(int $teams, int $avgMatch): int
    {
        return (int) ceil(2 * $this->singleElimMinutes($teams, $avgMatch));
    }

    private function roundRobinMinutes(int $teams, int $avgMatch): int
    {
        $matches = ($teams * ($teams - 1)) / 2;

        return (int) ceil($matches * $avgMatch);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function swissMinutes(int $teams, int $avgMatch, array $settings): int
    {
        $rounds = (int) ($settings['total_rounds'] ?? max(3, (int) ceil(log($teams, 2))));
        $matchesPerRound = (int) ceil($teams / 2);

        return $rounds * $matchesPerRound * $avgMatch;
    }

    /**
     * @param  array<string, mixed>  $inputs
     */
    private function hashInputs(array $inputs): string
    {
        ksort($inputs);

        return hash('sha256', (string) json_encode($inputs));
    }
}
