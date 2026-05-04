<?php

namespace App\Domain\Newsletter\Actions;

use App\Domain\Newsletter\Clients\ListmonkClient;
use App\Domain\Newsletter\Enums\SubscriptionStatus;
use App\Domain\Newsletter\Exceptions\ListmonkException;
use App\Domain\Newsletter\Models\NewsletterList;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Pulls a user's current per-list subscription status from Listmonk and
 * mirrors it into `newsletter_list_user`. Dispatched non-blocking by
 * `EmailSettingsController::edit()` and used by the nightly reconcile.
 *
 * Tolerates Listmonk being unavailable: logs a warning and short-circuits
 * (UX shows possibly-stale data, which is acceptable per plan).
 *
 * @see docs/mil-std-498/SRS.md NLT-F-004
 */
class RefreshUserSubscriptions
{
    public function __construct(private readonly ListmonkClient $listmonk) {}

    public function execute(User $user): void
    {
        if (! config('listmonk.enabled')) {
            return;
        }

        try {
            $remote = $this->listmonk->getSubscriberByEmail((string) $user->email);
        } catch (ListmonkException $e) {
            Log::warning('Newsletter: failed to refresh user subscriptions', [
                'user_id' => $user->id,
                'kind' => $e->kind,
                'message' => $e->getMessage(),
            ]);

            return;
        }

        $remoteListIds = [];
        $remoteSubscriberId = null;

        if ($remote !== null) {
            $remoteSubscriberId = (int) ($remote['id'] ?? 0);
            foreach ($remote['lists'] ?? [] as $entry) {
                $listmonkListId = (int) ($entry['id'] ?? 0);
                if ($listmonkListId === 0) {
                    continue;
                }

                $remoteListIds[$listmonkListId] = $this->mapStatus((string) ($entry['subscription_status'] ?? 'enabled'));
            }
        }

        $localLists = NewsletterList::query()
            ->where('is_user_selectable', true)
            ->get();

        DB::transaction(function () use ($user, $localLists, $remoteListIds, $remoteSubscriberId): void {
            foreach ($localLists as $list) {
                $status = $remoteListIds[$list->listmonk_id] ?? SubscriptionStatus::Unsubscribed;

                $list->subscribers()->syncWithoutDetaching([
                    $user->id => [
                        'listmonk_subscriber_id' => $remoteSubscriberId === 0 ? null : $remoteSubscriberId,
                        'status' => $status->value,
                        'last_synced_at' => now(),
                    ],
                ]);
            }
        });
    }

    private function mapStatus(string $remote): SubscriptionStatus
    {
        return match ($remote) {
            'unsubscribed' => SubscriptionStatus::Unsubscribed,
            'blocklisted' => SubscriptionStatus::Blocklisted,
            default => SubscriptionStatus::Enabled,
        };
    }
}
