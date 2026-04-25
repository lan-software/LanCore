<?php

namespace App\Console\Commands;

use App\Domain\Integration\Services\LancoreIntegrationsReconciler;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

/**
 * Reconcile `config/integrations.php` into the database.
 *
 * Invoked by the Helm umbrella chart's LanCore sub-chart via a
 * `pre-install,pre-upgrade` hook Job (see charts/lancore/templates/
 * job-integrations-sync.yaml). Can be run manually from a LanCore pod:
 *
 *   php artisan integrations:sync --dry-run
 *   php artisan integrations:sync
 *   php artisan integrations:sync --only=lanbrackets --only=lanentrance
 *
 * @see docs/mil-std-498/SRS.md INT-F-012
 * @see docs/mil-std-498/SSDD.md §5.4.5
 */
#[Signature('integrations:sync {--dry-run : Print the reconciliation plan without writing to the database} {--only=* : Limit reconciliation to these slugs} {--allow-missing-tokens : Permit slugs without a configured *_LANCORE_TOKEN to be seeded without auth credentials}')]
#[Description('Reconcile config/integrations.php against the database')]
class IntegrationsSyncCommand extends Command
{
    public function handle(LancoreIntegrationsReconciler $reconciler): int
    {
        /** @var list<string> $only */
        $only = (array) $this->option('only');
        $dryRun = (bool) $this->option('dry-run');
        $strict = ! (bool) $this->option('allow-missing-tokens');

        try {
            $summary = $reconciler->reconcile($only, $dryRun, $strict);
        } catch (Throwable $e) {
            $this->components->error("integrations:sync failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        if ($summary === []) {
            $this->components->warn('No slugs matched; nothing to reconcile.');

            return self::SUCCESS;
        }

        $this->components->info($dryRun ? 'Reconciliation plan (dry run):' : 'Reconciliation completed.');
        $rows = [];
        foreach ($summary as $entry) {
            $rows[] = [
                'slug' => $entry['slug'],
                'action' => $entry['created'] ? 'create' : 'update',
                'token' => $entry['token_rotated'] ? 'rotated' : '—',
                'webhooks' => (string) $entry['webhooks_refreshed'],
            ];
        }

        $this->table(['slug', 'action', 'token', 'webhooks'], $rows);

        return self::SUCCESS;
    }
}
