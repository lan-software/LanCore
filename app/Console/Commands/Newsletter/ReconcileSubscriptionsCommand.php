<?php

namespace App\Console\Commands\Newsletter;

use App\Domain\Newsletter\Jobs\ReconcileSubscriptionsJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('newsletter:reconcile-subscriptions')]
#[Description('Dispatch the nightly Listmonk subscription reconciliation fan-out.')]
class ReconcileSubscriptionsCommand extends Command
{
    public function handle(): int
    {
        if (! config('listmonk.enabled')) {
            $this->warn('Listmonk integration is disabled (LISTMONK_ENABLED=false).');

            return 2;
        }

        ReconcileSubscriptionsJob::dispatch();
        $this->info('Reconcile fan-out queued.');

        return self::SUCCESS;
    }
}
