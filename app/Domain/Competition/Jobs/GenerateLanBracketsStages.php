<?php

namespace App\Domain\Competition\Jobs;

use App\Domain\Api\Clients\LanBracketsClient;
use App\Domain\Competition\Exceptions\LanBracketsRequestException;
use App\Domain\Competition\Models\Competition;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Triggers LanBrackets bracket / match generation for every stage of the
 * given competition. Idempotent on the LanBrackets side (already-generated
 * stages return success), so safe to retry.
 *
 * Dispatched both from `Bus::chain` in `UpdateCompetition` (RegistrationClosed
 * transition) and from the `stage.completed` branch in `HandleLanBracketsWebhook`
 * (multi-stage progression).
 *
 * @see docs/mil-std-498/SRS.md COMP-F-016, COMP-F-019
 */
class GenerateLanBracketsStages implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(public readonly Competition $competition) {}

    public function handle(LanBracketsClient $client): void
    {
        $competition = $this->competition->fresh() ?? $this->competition;

        if (! $competition->isSyncedToLanBrackets()) {
            Log::warning('GenerateLanBracketsStages: Competition not synced to LanBrackets yet.', [
                'competition_id' => $competition->id,
            ]);

            return;
        }

        try {
            $stages = $client->getStages($competition->lanbrackets_id);

            if (empty($stages)) {
                Log::warning('GenerateLanBracketsStages: LanBrackets returned no stages.', [
                    'competition_id' => $competition->id,
                    'lanbrackets_id' => $competition->lanbrackets_id,
                ]);

                return;
            }

            foreach ($stages as $stage) {
                $stageId = $stage['id'] ?? null;
                if ($stageId === null) {
                    continue;
                }

                try {
                    $client->generateStage($competition->lanbrackets_id, (int) $stageId);
                } catch (LanBracketsRequestException $e) {
                    // 422 typically means "already generated" — log and continue.
                    Log::info('GenerateLanBracketsStages: stage generate skipped.', [
                        'competition_id' => $competition->id,
                        'lanbrackets_id' => $competition->lanbrackets_id,
                        'stage_id' => $stageId,
                        'status' => $e->getCode(),
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        } catch (Throwable $e) {
            Log::error('GenerateLanBracketsStages: unexpected failure.', [
                'competition_id' => $competition->id,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
