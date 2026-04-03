<?php

namespace App\Domain\Competition\Actions;

use App\Domain\Competition\Enums\CompetitionStatus;
use App\Domain\Competition\Jobs\SyncCompetitionToLanBrackets;
use App\Domain\Competition\Models\Competition;
use Illuminate\Support\Str;

/**
 * @see docs/mil-std-498/SRS.md COMP-F-001
 */
class CreateCompetition
{
    /**
     * @param  array{name: string, slug?: string, description?: string|null, event_id?: int|null, game_id?: int|null, game_mode_id?: int|null, type: string, stage_type: string, team_size?: int|null, max_teams?: int|null, registration_opens_at?: string|null, registration_closes_at?: string|null, starts_at?: string|null, ends_at?: string|null, settings?: array<string, mixed>|null}  $attributes
     */
    public function execute(array $attributes): Competition
    {
        if (! isset($attributes['slug'])) {
            $attributes['slug'] = Str::slug($attributes['name']);
        }

        if (! isset($attributes['status'])) {
            $attributes['status'] = CompetitionStatus::Draft;
        }

        $competition = Competition::create($attributes);

        if (config('lanbrackets.enabled')) {
            SyncCompetitionToLanBrackets::dispatch($competition);
        }

        return $competition;
    }
}
