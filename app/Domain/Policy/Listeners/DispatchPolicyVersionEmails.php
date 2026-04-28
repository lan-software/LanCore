<?php

namespace App\Domain\Policy\Listeners;

use App\Domain\Policy\Events\PolicyPublished;
use App\Domain\Policy\Jobs\SendPolicyPublishedEmailJob;
use App\Domain\Policy\Models\PolicyAcceptance;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Resolves prior acceptors of any version of the same policy and queues
 * one PolicyVersionPublishedNotification per recipient. Editorial publishes
 * are silent: this listener returns early.
 *
 * @see docs/mil-std-498/SSS.md CAP-POL-005
 * @see docs/mil-std-498/SRS.md POL-F-015
 */
class DispatchPolicyVersionEmails implements ShouldQueue
{
    public function handle(PolicyPublished $event): void
    {
        if ($event->silent || ! $event->isNonEditorial) {
            return;
        }

        $policyId = $event->policy->id;

        $userIds = PolicyAcceptance::query()
            ->whereHas('version', fn ($q) => $q
                ->where('policy_id', $policyId)
                ->where('version_number', '<', $event->versionNumber))
            ->whereNull('withdrawn_at')
            ->distinct()
            ->pluck('user_id');

        foreach ($userIds->chunk(500) as $chunk) {
            foreach ($chunk as $userId) {
                SendPolicyPublishedEmailJob::dispatch(
                    (int) $userId,
                    $policyId,
                    $event->versionNumber,
                );
            }
        }
    }
}
