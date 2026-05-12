<?php

namespace App\Domain\Competition\Actions;

use App\Domain\Competition\Enums\CompetitionStatus;
use App\Domain\Competition\Jobs\GenerateLanBracketsStages;
use App\Domain\Competition\Jobs\SyncCompetitionToLanBrackets;
use App\Domain\Competition\Jobs\SyncTeamsToLanBrackets;
use App\Domain\Competition\Models\Competition;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * @see docs/mil-std-498/SRS.md COMP-F-003
 */
class UpdateCompetition
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Competition $competition, array $attributes): Competition
    {
        if (isset($attributes['name']) && ! isset($attributes['slug'])) {
            $attributes['slug'] = Str::slug($attributes['name']);
        }

        if (isset($attributes['status'])) {
            $targetStatus = $attributes['status'] instanceof CompetitionStatus
                ? $attributes['status']
                : CompetitionStatus::from($attributes['status']);

            if (! $competition->status->canTransitionTo($targetStatus)) {
                throw ValidationException::withMessages([
                    'status' => "Cannot transition from {$competition->status->value} to {$targetStatus->value}.",
                ]);
            }

            // When registration closes, sync the team roster to LanBrackets
            // and *then* trigger bracket / match generation. The two run as a
            // chain so generation only fires once the participants table on the
            // LanBrackets side is populated.
            if ($targetStatus === CompetitionStatus::RegistrationClosed && config('lanbrackets.enabled')) {
                Bus::chain([
                    new SyncTeamsToLanBrackets($competition),
                    new GenerateLanBracketsStages($competition),
                ])->dispatch();
            }
        }

        $competition->update($attributes);

        // Always dispatch when LanBrackets is enabled: the job handles both
        // "create" (unsynced) and "update" (already synced) paths, so any
        // edit transparently retries a previously-failed initial sync.
        // Earlier code gated this behind `isSyncedToLanBrackets()` which made
        // failed initial syncs unrecoverable from the UI (COMP-F-010).
        if (config('lanbrackets.enabled')) {
            SyncCompetitionToLanBrackets::dispatch($competition);
        }

        return $competition;
    }
}
