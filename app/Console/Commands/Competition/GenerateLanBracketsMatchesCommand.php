<?php

namespace App\Console\Commands\Competition;

use App\Domain\Api\Clients\LanBracketsClient;
use App\Domain\Competition\Jobs\GenerateLanBracketsStages;
use App\Domain\Competition\Jobs\SyncTeamsToLanBrackets;
use App\Domain\Competition\Models\Competition;
use Illuminate\Console\Command;

/**
 * Manually run the "registration-closed" LanBrackets pipeline for one
 * competition: re-sync participants, then trigger bracket / match generation.
 *
 * Recovery path for competitions that closed registration before the
 * post-registration pipeline existed (or before participants finished syncing).
 *
 * Examples:
 *   php artisan competitions:generate-matches 2
 *   php artisan competitions:generate-matches 2 --sync
 *
 * @see docs/mil-std-498/SRS.md COMP-F-017, COMP-F-018
 */
class GenerateLanBracketsMatchesCommand extends Command
{
    protected $signature = 'competitions:generate-matches
        {id : Competition id whose LanBrackets stages should be generated.}
        {--sync : Run the jobs inline (synchronously) instead of queuing.}';

    protected $description = 'Re-sync participants and trigger LanBrackets bracket / match generation for a competition.';

    public function handle(): int
    {
        if (! config('lanbrackets.enabled')) {
            $this->error('LanBrackets integration is disabled.');

            return self::FAILURE;
        }

        $competition = Competition::find((int) $this->argument('id'));

        if ($competition === null) {
            $this->error("Competition #{$this->argument('id')} not found.");

            return self::FAILURE;
        }

        if (! $competition->isSyncedToLanBrackets()) {
            $this->error("Competition #{$competition->id} has no lanbrackets_id — run competitions:resync first.");

            return self::FAILURE;
        }

        $sync = (bool) $this->option('sync');
        $client = app(LanBracketsClient::class);

        $this->line(sprintf(
            '%s LanBrackets participant + bracket pipeline for competition #%d "%s"',
            $sync ? 'Running' : 'Dispatching',
            $competition->id,
            $competition->name,
        ));

        $teamSync = new SyncTeamsToLanBrackets($competition);
        $generate = new GenerateLanBracketsStages($competition);

        if ($sync) {
            $teamSync->handle($client);
            $generate->handle($client);
            $this->info('Done. Check LanBrackets for the generated matches.');
        } else {
            dispatch($teamSync);
            dispatch($generate);
            $this->info('Jobs queued.');
        }

        return self::SUCCESS;
    }
}
