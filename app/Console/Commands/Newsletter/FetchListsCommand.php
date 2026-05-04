<?php

namespace App\Console\Commands\Newsletter;

use App\Domain\Newsletter\Actions\FetchListsFromListmonk;
use App\Domain\Newsletter\Exceptions\ListmonkException;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('newsletter:lists:fetch')]
#[Description('Pull the Listmonk list catalog and refresh the local mirror.')]
class FetchListsCommand extends Command
{
    public function handle(FetchListsFromListmonk $action): int
    {
        if (! config('listmonk.enabled')) {
            $this->warn('Listmonk integration is disabled (LISTMONK_ENABLED=false).');

            return 2;
        }

        try {
            $stats = $action->execute();
        } catch (ListmonkException $e) {
            $this->error("Listmonk fetch failed [{$e->kind}]: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Newsletter lists synced: %d created, %d updated, %d removed.',
            $stats['created'],
            $stats['updated'],
            $stats['removed'],
        ));

        return self::SUCCESS;
    }
}
