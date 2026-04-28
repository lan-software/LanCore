<?php

namespace App\Domain\Policy\Jobs;

use App\Domain\Policy\Models\Policy;
use App\Domain\Policy\Notifications\PolicyVersionPublishedNotification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

/**
 * Sends the PolicyVersionPublishedNotification to a single recipient. Picks
 * the locale row of the publish that matches the user's preferred locale, with
 * fallback as defined by Policy::versionForLocale().
 *
 * Fan-out is handled by DispatchPolicyVersionEmails which dispatches one of
 * these per prior acceptor — never per locale row.
 *
 * @see docs/mil-std-498/SSS.md CAP-POL-005
 */
class SendPolicyPublishedEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $userId,
        private readonly int $policyId,
        private readonly int $versionNumber,
    ) {}

    public function handle(): void
    {
        $user = User::query()->find($this->userId);
        $policy = Policy::query()->find($this->policyId);

        if ($user === null || $policy === null) {
            return;
        }

        $locale = property_exists($user, 'locale') && $user->locale
            ? (string) $user->locale
            : (string) app()->getLocale();

        $version = $policy->versionForLocale($this->versionNumber, $locale);

        if ($version === null) {
            return;
        }

        $version->setRelation('policy', $policy);

        Notification::send($user, new PolicyVersionPublishedNotification($version));
    }
}
