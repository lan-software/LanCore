<?php

namespace App\Domain\DataLifecycle\Console\Commands;

use App\Domain\DataLifecycle\Actions\PurgeExpiredData;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('lifecycle:purge {--dry-run}')]
#[Description('Walk soft-deleted+anonymized users and purge data whose retention has expired. Use --dry-run to preview.')]
class PurgeExpiredDataCommand extends Command
{
    public function handle(PurgeExpiredData $action): int
    {
        $stats = $action->execute(dryRun: (bool) $this->option('dry-run'));

        $this->table(
            ['users_purged', 'anonymizers_run', 'dry_run'],
            [[
                $stats['users_purged'],
                $stats['anonymizers_run'],
                $stats['dry_run'] ? 'yes' : 'no',
            ]],
        );

        return self::SUCCESS;
    }
}
