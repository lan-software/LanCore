<?php

namespace App\Domain\Newsletter\Jobs;

use App\Domain\Newsletter\Models\NewsletterList;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Daily fan-out parent. Walks every local `NewsletterList` and dispatches
 * one `ReconcileListSubscriptionsJob` per row. Scheduled at 03:45 in
 * `routes/console.php` with `withoutOverlapping()->onOneServer()`.
 *
 * @see docs/mil-std-498/SRS.md NLT-F-004
 */
class ReconcileSubscriptionsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function handle(): void
    {
        if (! config('listmonk.enabled')) {
            return;
        }

        NewsletterList::query()
            ->select(['id'])
            ->chunkById(50, function ($lists): void {
                foreach ($lists as $list) {
                    ReconcileListSubscriptionsJob::dispatch($list->id);
                }
            });
    }
}
