<?php

namespace App\Domain\Newsletter\Actions;

use App\Domain\Newsletter\Jobs\SubscribeUserJob;
use App\Domain\Newsletter\Models\NewsletterList;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * "Silently opts in all users" admin action. Walks every verified user
 * in chunks and queues a `SubscribeUserToList` call for each. Bulk path:
 * when the list has thousands of users, this dispatches as queued jobs
 * (one per user) so the admin request returns quickly.
 *
 * @see docs/mil-std-498/SRS.md NLT-F-005
 */
class OptInAllUsersToList
{
    public function execute(NewsletterList $list): int
    {
        $count = 0;

        User::query()
            ->whereNotNull('email_verified_at')
            ->select(['id'])
            ->chunkById(500, function ($users) use ($list, &$count): void {
                foreach ($users as $user) {
                    DB::afterCommit(function () use ($user, $list): void {
                        SubscribeUserJob::dispatch($user->id, $list->id);
                    });
                    $count++;
                }
            });

        return $count;
    }
}
