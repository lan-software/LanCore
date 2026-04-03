<?php

namespace App\Domain\Competition\Actions;

use App\Domain\Competition\Enums\CompetitionStatus;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\MatchResultProof;

/**
 * @see docs/mil-std-498/SRS.md COMP-F-009
 */
class HandleLanBracketsWebhook
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(string $event, array $payload): void
    {
        match ($event) {
            'competition.completed' => $this->handleCompetitionCompleted($payload),
            'match.result_reported' => $this->handleMatchResultReported($payload),
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
    }
}
