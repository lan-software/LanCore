<?php

namespace App\Domain\Policy\Listeners;

use App\Domain\Policy\Events\PolicyVersionPublished;
use App\Domain\Policy\Jobs\SendPolicyVersionPublishedEmailJob;
use App\Domain\Policy\Models\PolicyAcceptance;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Resolves prior acceptors of any version of the same policy and queues
 * one PolicyVersionPublishedNotification per recipient. Editorial
 * publishes are silent: this listener returns early.
 *
 * @see docs/mil-std-498/SSS.md CAP-POL-005
 * @see docs/mil-std-498/SRS.md POL-F-015
 */
class DispatchPolicyVersionEmails implements ShouldQueue
{
    public function handle(PolicyVersionPublished $event): void
    {
        if ($event->silent || ! $event->isNonEditorial) {
            return;
        }

        $newVersion = $event->version;
        $policyId = $newVersion->policy_id;

        $userIds = PolicyAcceptance::query()
            ->whereHas('version', fn ($q) => $q->where('policy_id', $policyId))
            ->where('policy_version_id', '!=', $newVersion->id)
            ->distinct()
            ->pluck('user_id');

        foreach ($userIds->chunk(500) as $chunk) {
            foreach ($chunk as $userId) {
                SendPolicyVersionPublishedEmailJob::dispatch((int) $userId, $newVersion->id);
            }
        }
    }
}
