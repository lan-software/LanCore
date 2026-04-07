<?php

namespace App\Domain\Competition\Jobs;

use App\Domain\Api\Clients\LanBracketsClient;
use App\Domain\Competition\Models\Competition;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * @see docs/mil-std-498/SRS.md COMP-F-010
 */
class SyncCompetitionToLanBrackets implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(public readonly Competition $competition) {}

    public function handle(LanBracketsClient $client): void
    {
        $competition = $this->competition;

        if ($competition->isSyncedToLanBrackets()) {
            $client->updateCompetition($competition->lanbrackets_id, [
                'name' => $competition->name,
                'description' => $competition->description,
            ]);

            return;
        }

        $response = $client->createCompetition([
            'name' => $competition->name,
            'type' => $competition->type->value,
            'stage_type' => $competition->stage_type->value,
            'description' => $competition->description,
            'external_reference_id' => (string) $competition->id,
            'source_system' => 'lancore',
        ]);

        $lanbracketsId = $response['id'] ?? null;

        if ($lanbracketsId === null) {
            Log::error('SyncCompetitionToLanBrackets: No ID returned from LanBrackets.', [
                'competition_id' => $competition->id,
                'response' => $response,
            ]);

            return;
        }

        $shareToken = $client->regenerateShareToken($lanbracketsId);

        $competition->update([
            'lanbrackets_id' => $lanbracketsId,
            'lanbrackets_share_token' => $shareToken,
        ]);
    }
}
