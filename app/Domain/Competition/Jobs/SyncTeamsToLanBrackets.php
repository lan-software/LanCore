<?php

namespace App\Domain\Competition\Jobs;

use App\Domain\Api\Clients\LanBracketsClient;
use App\Domain\Competition\Exceptions\LanBracketsRequestException;
use App\Domain\Competition\Models\Competition;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * @see docs/mil-std-498/SRS.md COMP-F-011
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
            $participants[] = [
                'participant_type' => 'team',
                'participant_id' => $team->lanbrackets_id ?? $team->id,
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
}
