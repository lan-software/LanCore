<?php

namespace App\Domain\Competition\Jobs;

use App\Domain\Api\Clients\LanBracketsClient;
use App\Domain\Competition\Exceptions\LanBracketsRequestException;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\CompetitionTeam;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Syncs each `CompetitionTeam` to LanBrackets in two phases:
 *
 *   1. Per-team upsert via `LanBracketsClient::upsertTeam()` to bridge the
 *      team identity gap. The returned LanBrackets id is persisted into
 *      `CompetitionTeam.lanbrackets_id` so subsequent participant rows on
 *      LanBrackets reference the canonical team, not whatever local id
 *      happens to collide.
 *   2. One bulk participant call carrying `participant_id = team->lanbrackets_id`.
 *
 * @see docs/mil-std-498/SRS.md COMP-F-011, COMP-F-018
 */
class SyncTeamsToLanBrackets implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(public readonly Competition $competition) {}

    public function handle(LanBracketsClient $client): void
    {
        $competition = $this->competition;

        if (! $competition->isSyncedToLanBrackets()) {
            Log::warning('SyncTeamsToLanBrackets: Competition not synced to LanBrackets yet.', [
                'competition_id' => $competition->id,
            ]);

            return;
        }

        $teams = $competition->teams()->with('activeMembers')->get();

        $participants = [];

        foreach ($teams as $team) {
            if (! $this->upsertTeam($client, $competition->id, $team)) {
                continue;
            }

            if ($team->lanbrackets_id === null) {
                continue;
            }

            $participants[] = [
                'participant_type' => 'team',
                'participant_id' => $team->lanbrackets_id,
            ];
        }

        if (empty($participants)) {
            return;
        }

        try {
            $client->bulkAddParticipants($competition->lanbrackets_id, $participants);
        } catch (LanBracketsRequestException $e) {
            // Already-registered participants surface as a 4xx — treat as idempotent
            // success so the chained generation step still runs. The Bus chain
            // halts on uncaught exceptions, which previously left the bracket
            // ungenerated whenever the sync ran twice.
            if ($e->getCode() >= 400 && $e->getCode() < 500) {
                Log::info('SyncTeamsToLanBrackets: bulk add returned 4xx — treating as already-synced.', [
                    'competition_id' => $competition->id,
                    'status' => $e->getCode(),
                    'message' => $e->getMessage(),
                ]);

                return;
            }

            throw $e;
        }
    }

    /**
     * Returns true when the team should remain in the bulk participant payload.
     * Returns false only when the upsert failed with a 4xx AND the team has no
     * prior `lanbrackets_id` to fall back on. 5xx errors re-raise so the queue
     * retries.
     */
    private function upsertTeam(LanBracketsClient $client, string $competitionId, CompetitionTeam $team): bool
    {
        $payload = [
            'name' => $team->name,
            'tag' => $team->tag,
            'description' => $team->description ?? null,
            'external_reference_id' => (string) $team->id,
            'source_system' => 'lancore',
        ];

        try {
            $returned = $client->upsertTeam($payload);
        } catch (LanBracketsRequestException $e) {
            if ($e->getCode() >= 500 || $e->getCode() === 0) {
                throw $e;
            }

            Log::info('SyncTeamsToLanBrackets: upsertTeam returned 4xx.', [
                'competition_id' => $competitionId,
                'team_id' => $team->id,
                'status' => $e->getCode(),
                'message' => $e->getMessage(),
            ]);

            // Without a prior `lanbrackets_id` we have nothing to send to the
            // bulk participant endpoint, so skip this team.
            return $team->lanbrackets_id !== null;
        }

        $returnedId = isset($returned['id']) ? (string) $returned['id'] : null;

        if ($returnedId !== null && $returnedId !== $team->lanbrackets_id) {
            $team->update(['lanbrackets_id' => $returnedId]);
        }

        return true;
    }
}
