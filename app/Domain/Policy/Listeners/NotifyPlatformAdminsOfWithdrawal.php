<?php

namespace App\Domain\Policy\Listeners;

use App\Domain\Policy\Enums\Permission as PolicyPermission;
use App\Domain\Policy\Events\ConsentWithdrawn;
use App\Domain\Policy\Notifications\UserWithdrewConsentNotification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

/**
 * Notifies every user holding ManagePolicies that a peer withdrew
 * consent. The withdrawing user is excluded from the recipients.
 *
 * @see docs/mil-std-498/SSS.md CAP-POL-008
 * @see docs/mil-std-498/SRS.md POL-F-014
 */
class NotifyPlatformAdminsOfWithdrawal implements ShouldQueue
{
    public function handle(ConsentWithdrawn $event): void
    {
        $admins = User::query()
            ->where('id', '!=', $event->acceptance->user_id)
            ->get()
            ->filter(fn (User $user) => $user->hasPermission(PolicyPermission::ManagePolicies));

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new UserWithdrewConsentNotification($event->acceptance));
    }
}
