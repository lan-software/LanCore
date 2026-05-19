<?php

namespace App\Domain\CompetitionSchedule\ValueObjects;

/**
 * One row of the live next-match queue.
 *
 * @see docs/mil-std-498/SRS.md COMP-SCH-005
 */
final readonly class MatchProposal
{
    /**
     * @param  array<int, array{participant_id?: int|string, name?: string, team_id?: int|string}>  $participants
     * @param  array<int, string>  $reasonKeys
     */
    public function __construct(
        public string $competitionId,
        public string $competitionName,
        public string $stageId,
        public string $stageName,
        public int|string $matchId,
        public int $score,
        public bool $blocked,
        public array $participants,
        public array $reasonKeys,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'competition_id' => $this->competitionId,
            'competition_name' => $this->competitionName,
            'stage_id' => $this->stageId,
            'stage_name' => $this->stageName,
            'match_id' => $this->matchId,
            'score' => $this->score,
            'blocked' => $this->blocked,
            'participants' => $this->participants,
            'reason_keys' => $this->reasonKeys,
        ];
    }
}
