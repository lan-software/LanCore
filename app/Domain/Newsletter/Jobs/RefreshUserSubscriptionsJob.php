<?php

namespace App\Domain\Newsletter\Jobs;

use App\Domain\Newsletter\Actions\RefreshUserSubscriptions;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched non-blocking from `EmailSettingsController::edit()`. The
 * page renders with possibly-stale data; the next visit reflects the
 * refreshed state. Acceptable per plan (1% drift tolerance).
 *
 * @see docs/mil-std-498/SRS.md NLT-F-004
 */
class RefreshUserSubscriptionsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public function __construct(public readonly int $userId) {}

    public function handle(RefreshUserSubscriptions $action): void
    {
        $user = User::find($this->userId);

        if ($user === null) {
            return;
        }

        $action->execute($user);
    }
}
