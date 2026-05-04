<?php

namespace App\Domain\Newsletter\Jobs;

use App\Domain\Newsletter\Actions\SubscribeUserToList;
use App\Domain\Newsletter\Models\NewsletterList;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Per-user fan-out worker for `OptInAllUsersToList`. Wraps a single
 * `SubscribeUserToList::execute()` call so admin requests stay fast
 * even on five-figure user counts.
 *
 * @see docs/mil-std-498/SRS.md NLT-F-005
 */
class SubscribeUserJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly int $userId,
        public readonly int $newsletterListId,
    ) {}

    public function handle(SubscribeUserToList $action): void
    {
        $user = User::find($this->userId);
        $list = NewsletterList::find($this->newsletterListId);

        if ($user === null || $list === null) {
            return;
        }

        $action->execute($user, $list, preconfirm: true);
    }
}
