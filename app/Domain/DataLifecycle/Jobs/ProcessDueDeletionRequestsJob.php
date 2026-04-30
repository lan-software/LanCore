<?php

namespace App\Domain\DataLifecycle\Jobs;

use App\Domain\DataLifecycle\Actions\AnonymizeUser;
use App\Domain\DataLifecycle\Enums\DeletionRequestStatus;
use App\Domain\DataLifecycle\Models\DeletionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Walks pending_grace deletion requests whose scheduled_for is past, and
 * anonymizes them. Runs nightly from the scheduler.
 */
class ProcessDueDeletionRequestsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(AnonymizeUser $anonymize): void
    {
        DeletionRequest::query()
            ->where('status', DeletionRequestStatus::PendingGrace->value)
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '<=', now())
            ->orderBy('id')
            ->lazyById(50)
            ->each(function (DeletionRequest $request) use ($anonymize): void {
                $anonymize->execute($request);
            });
    }
}
