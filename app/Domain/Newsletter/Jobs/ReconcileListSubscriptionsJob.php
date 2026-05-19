<?php

namespace App\Domain\Newsletter\Jobs;

use App\Domain\Newsletter\Actions\ReconcileSubscriptionsForList;
use App\Domain\Newsletter\Models\NewsletterList;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Single-list reconciliation worker. Spawned by `ReconcileSubscriptionsJob`
 * (the daily fan-out parent) — one worker per local `NewsletterList`.
 *
 * @see docs/mil-std-498/SRS.md NLT-F-004
 */
class ReconcileListSubscriptionsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(public readonly string $newsletterListId) {}

    public function handle(ReconcileSubscriptionsForList $action): void
    {
        $list = NewsletterList::find($this->newsletterListId);

        if ($list === null) {
            return;
        }

        $action->execute($list);
    }
}
