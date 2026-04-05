<?php

namespace App\Domain\Competition\Actions;

use App\Domain\Competition\Enums\CompetitionStatus;
use App\Domain\Competition\Jobs\SyncCompetitionToLanBrackets;
use App\Domain\Competition\Jobs\SyncTeamsToLanBrackets;
use App\Domain\Competition\Models\Competition;
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

            if ($targetStatus === CompetitionStatus::RegistrationClosed && config('lanbrackets.enabled')) {
                SyncTeamsToLanBrackets::dispatch($competition);
            }
        }

        $competition->update($attributes);

        if (config('lanbrackets.enabled') && $competition->isSyncedToLanBrackets()) {
            SyncCompetitionToLanBrackets::dispatch($competition);
        }

        return $competition;
    }
}
