<?php

namespace App\Domain\Newsletter\Actions;

use App\Domain\Newsletter\Clients\ListmonkClient;
use App\Domain\Newsletter\Enums\SubscriptionStatus;
use App\Domain\Newsletter\Models\NewsletterList;
use App\Domain\Newsletter\Models\NewsletterSubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Asks Listmonk to unsubscribe the user from a single list, then mirrors
 * the resulting status into `newsletter_list_user`.
 *
 * @see docs/mil-std-498/SRS.md NLT-F-003
 */
class UnsubscribeUserFromList
{
    public function __construct(private readonly ListmonkClient $listmonk) {}

    public function execute(User $user, NewsletterList $list): void
    {
        $pivot = NewsletterSubscription::query()
            ->where('newsletter_list_id', $list->id)
            ->where('user_id', $user->id)
            ->first();

        $subscriberId = $pivot?->listmonk_subscriber_id;

        if ($subscriberId === null) {
            $remote = $this->listmonk->getSubscriberByEmail((string) $user->email);
            $subscriberId = $remote === null ? null : (int) $remote['id'];
        }

        if ($subscriberId !== null) {
            $this->listmonk->manageSubscriberLists(
                subscriberId: $subscriberId,
                listIds: [$list->listmonk_id],
                action: 'unsubscribe',
            );
        }

        DB::transaction(function () use ($user, $list, $subscriberId): void {
            $list->subscribers()->syncWithoutDetaching([
                $user->id => [
                    'listmonk_subscriber_id' => $subscriberId,
                    'status' => SubscriptionStatus::Unsubscribed->value,
                    'last_synced_at' => now(),
                ],
            ]);
        });
    }
}
