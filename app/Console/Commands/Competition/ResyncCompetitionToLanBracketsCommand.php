<?php

namespace App\Console\Commands\Competition;

use App\Domain\Api\Clients\LanBracketsClient;
use App\Domain\Competition\Jobs\SyncCompetitionToLanBrackets;
use App\Domain\Competition\Models\Competition;
use Illuminate\Console\Command;

/**
 * Manual re-dispatch of `SyncCompetitionToLanBrackets` for one or all
 * competitions. Use this to recover from a failed initial sync that left the
 * competition with `lanbrackets_id = null` and no UI affordance to retry.
 *
 * Examples:
 *   php artisan competitions:resync                # dispatch for every competition
 *   php artisan competitions:resync 42             # only competition id 42
 *   php artisan competitions:resync --unsynced     # only those with lanbrackets_id null
 *
 * @see docs/mil-std-498/SRS.md COMP-F-010
 */
class ResyncCompetitionToLanBracketsCommand extends Command
{
    protected $signature = 'competitions:resync
        {id? : Optional competition id; omit to dispatch for all competitions.}
        {--unsynced : Restrict to competitions with lanbrackets_id null (failed initial syncs).}
        {--sync : Run the job inline (synchronously) instead of queuing — useful for debugging the failure.}';

    protected $description = 'Re-dispatch the SyncCompetitionToLanBrackets job for one or all competitions.';

    public function handle(): int
    {
        if (! config('lanbrackets.enabled')) {
            $this->error('LanBrackets integration is disabled (config/lanbrackets.php → enabled = false).');

            return self::FAILURE;
        }

        $query = Competition::query();

        if ($id = $this->argument('id')) {
            $query->where('id', (string) $id);
        }

        if ($this->option('unsynced')) {
            $query->whereNull('lanbrackets_id');
        }

        $competitions = $query->get();

        if ($competitions->isEmpty()) {
            $this->warn('No competitions matched.');

            return self::SUCCESS;
        }

        $sync = (bool) $this->option('sync');

        foreach ($competitions as $competition) {
            $job = new SyncCompetitionToLanBrackets($competition);

            $this->line(sprintf(
                '%s competition #%d "%s" (lanbrackets_id=%s)',
                $sync ? 'Running' : 'Dispatching',
                $competition->id,
                $competition->name,
                $competition->lanbrackets_id ?? 'null',
            ));

            if ($sync) {
                $job->handle(app(LanBracketsClient::class));
                $this->info(sprintf(
                    '  → lanbrackets_id=%s share_token=%s',
                    $competition->fresh()->lanbrackets_id ?? 'null',
                    $competition->fresh()->lanbrackets_share_token ? 'set' : 'null',
                ));
            } else {
                dispatch($job);
            }
        }

        $this->info(sprintf(
            'Done. %d competition(s) %s.',
            $competitions->count(),
            $sync ? 'synced' : 'queued for sync',
        ));

        return self::SUCCESS;
    }
}
