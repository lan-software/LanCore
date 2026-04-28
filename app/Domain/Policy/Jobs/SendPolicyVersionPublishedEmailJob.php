<?php

namespace App\Domain\Policy\Jobs;

use App\Domain\Policy\Models\PolicyVersion;
use App\Domain\Policy\Notifications\PolicyVersionPublishedNotification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

/**
 * Sends the PolicyVersionPublishedNotification to a single recipient.
 * Fan-out is handled by DispatchPolicyVersionEmails which dispatches
 * one of these per prior acceptor.
 *
 * @see docs/mil-std-498/SSS.md CAP-POL-005
 */
class SendPolicyVersionPublishedEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $userId,
        private readonly int $policyVersionId,
    ) {}

    public function handle(): void
    {
        $user = User::query()->find($this->userId);
        $version = PolicyVersion::query()->with('policy')->find($this->policyVersionId);

        if ($user === null || $version === null) {
            return;
        }

        Notification::send($user, new PolicyVersionPublishedNotification($version));
    }
}
