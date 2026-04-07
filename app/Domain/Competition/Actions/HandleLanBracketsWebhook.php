<?php

namespace App\Domain\Competition\Actions;

use App\Domain\Api\Clients\LanBracketsClient;
use App\Domain\Competition\Enums\CompetitionStatus;
use App\Domain\Competition\Events\MatchCompleted;
use App\Domain\Competition\Events\MatchReadyForOrchestration;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\MatchResultProof;
use App\Domain\Orchestration\Models\OrchestrationJob;

/**
 * @see docs/mil-std-498/SRS.md COMP-F-009
 */
class HandleLanBracketsWebhook
{
    public function __construct(
        private readonly LanBracketsClient $lanBracketsClient,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(string $event, array $payload): void
    {
        match ($event) {
            'competition.completed' => $this->handleCompetitionCompleted($payload),
            'match.result_reported' => $this->handleMatchResultReported($payload),
            'bracket.generated' => $this->handleBracketGenerated($payload),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleCompetitionCompleted(array $payload): void
    {
        $competitionData = $payload['data']['competition'] ?? $payload['data'] ?? [];
        $externalReferenceId = $competitionData['external_reference_id'] ?? null;

        if ($externalReferenceId === null) {
            return;
        }

        $competition = Competition::where('id', $externalReferenceId)
            ->where('status', '!=', CompetitionStatus::Finished)
            ->first();

        if ($competition) {
            $competition->update(['status' => CompetitionStatus::Finished]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleMatchResultReported(array $payload): void
    {
        $matchData = $payload['data']['match'] ?? $payload['data'] ?? [];
        $matchId = $matchData['id'] ?? null;

        if ($matchId === null) {
            return;
        }

        MatchResultProof::where('lanbrackets_match_id', $matchId)
            ->whereNull('resolved_at')
            ->update(['resolved_at' => now()]);

        $externalReferenceId = $matchData['external_reference_id'] ?? $payload['data']['external_reference_id'] ?? null;
        $competition = $externalReferenceId !== null
            ? Competition::find($externalReferenceId)
            : null;

        if ($competition !== null) {
            MatchCompleted::dispatch($competition, (int) $matchId, $matchData);

            $this->dispatchReadyMatchesForOrchestration(
                $competition,
                $matchData['stage_id'] ?? null
            );
        }
    }

    /**
     * Handles bracket.generated webhook — orchestrates first-round matches
     * where all participants are already set.
     *
     * @param  array<string, mixed>  $payload
     */
    private function handleBracketGenerated(array $payload): void
    {
        $data = $payload['data'] ?? $payload;
        $externalReferenceId = $data['external_reference_id'] ?? null;
        $stageId = $data['stage_id'] ?? null;

        if ($externalReferenceId === null || $stageId === null) {
            return;
        }

        $competition = Competition::find($externalReferenceId);

        if ($competition === null || ! $competition->isSyncedToLanBrackets()) {
            return;
        }

        $this->dispatchReadyMatchesForOrchestration($competition, $stageId);
    }

    /**
     * Fetches matches for a stage and dispatches orchestration events
     * for matches where all participants are set.
     */
    private function dispatchReadyMatchesForOrchestration(Competition $competition, ?int $stageId): void
    {
        if ($stageId === null || ! $competition->isSyncedToLanBrackets()) {
            return;
        }

        try {
            $matches = $this->lanBracketsClient->getMatches(
                $competition->lanbrackets_id,
                $stageId
            );
        } catch (\Throwable) {
            return;
        }

        foreach ($matches as $match) {
            if (! $this->isMatchReady($match)) {
                continue;
            }

            $matchId = (int) $match['id'];

            $alreadyOrchestrated = OrchestrationJob::where('competition_id', $competition->id)
                ->where('lanbrackets_match_id', $matchId)
                ->exists();

            if ($alreadyOrchestrated) {
                continue;
            }

            MatchReadyForOrchestration::dispatch($competition, $matchId, $match);
        }
    }

    /**
     * A match is ready when all participant slots are filled and it's still pending.
     *
     * @param  array<string, mixed>  $match
     */
    private function isMatchReady(array $match): bool
    {
        if (($match['status'] ?? '') !== 'pending') {
            return false;
        }

        $participants = $match['participants'] ?? [];

        if (empty($participants)) {
            return false;
        }

        foreach ($participants as $participant) {
            if (empty($participant['competition_participant_id'])) {
                return false;
            }
        }

        return true;
    }
}
