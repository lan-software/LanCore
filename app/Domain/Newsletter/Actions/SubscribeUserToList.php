<?php

namespace App\Domain\Newsletter\Actions;

use App\Domain\Newsletter\Clients\ListmonkClient;
use App\Domain\Newsletter\Enums\SubscriptionStatus;
use App\Domain\Newsletter\Models\NewsletterList;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Pushes a user → Listmonk subscription, then mirrors the resulting
 * status back into `newsletter_list_user`. Used by the user E-Mail
 * Settings card and the public `/countdown` form.
 *
 * @see docs/mil-std-498/SRS.md NLT-F-003, NLT-F-006
 */
class SubscribeUserToList
{
    public function __construct(private readonly ListmonkClient $listmonk) {}

    public function execute(User $user, NewsletterList $list, ?bool $preconfirm = null): void
    {
        $preconfirm ??= (bool) config('listmonk.preconfirm_subscriptions');

        $remote = $this->listmonk->upsertSubscriber(
            email: (string) $user->email,
            name: $user->name,
            listIds: [$list->listmonk_id],
            preconfirm: $preconfirm,
        );

        $subscriberId = (int) ($remote['id'] ?? 0);
        $status = $preconfirm ? SubscriptionStatus::Enabled : SubscriptionStatus::Enabled;

        DB::transaction(function () use ($user, $list, $subscriberId, $status): void {
            $list->subscribers()->syncWithoutDetaching([
                $user->id => [
                    'listmonk_subscriber_id' => $subscriberId === 0 ? null : $subscriberId,
                    'status' => $status->value,
                    'subscribed_at' => now(),
                    'last_synced_at' => now(),
                ],
            ]);
        });
    }
}
