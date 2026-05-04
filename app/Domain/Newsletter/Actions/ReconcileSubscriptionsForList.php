<?php

namespace App\Domain\Newsletter\Actions;

use App\Domain\Newsletter\Clients\ListmonkClient;
use App\Domain\Newsletter\Enums\SubscriptionStatus;
use App\Domain\Newsletter\Models\NewsletterList;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Walks every Listmonk subscriber on the list (paginated) and upserts
 * matching local pivot rows. Local users not in the remote set are
 * marked `unsubscribed`. Remote subscribers without a matching local
 * `User` are skipped (they're either anonymous public-form signups or
 * pre-existing Listmonk-only subscribers, both correctly excluded from
 * the user-facing E-Mail Settings card).
 *
 * Runs nightly via `ReconcileSubscriptionsJob`; safety net catching
 * users who never visit `/settings/email`.
 *
 * @see docs/mil-std-498/SRS.md NLT-F-004
 */
class ReconcileSubscriptionsForList
{
    public function __construct(private readonly ListmonkClient $listmonk) {}

    public function execute(NewsletterList $list): void
    {
        $page = 1;
        $perPage = 100;
        $remoteByEmail = [];

        while (true) {
            $response = $this->listmonk->getSubscribersOfList($list->listmonk_id, $page, $perPage);
            $results = $response['results'] ?? [];

            if ($results === []) {
                break;
            }

            foreach ($results as $row) {
                $email = strtolower((string) ($row['email'] ?? ''));
                if ($email === '') {
                    continue;
                }

                $remoteByEmail[$email] = [
                    'listmonk_subscriber_id' => (int) ($row['id'] ?? 0),
                    'status' => $this->mapStatus((string) ($row['status'] ?? 'enabled')),
                ];
            }

            $total = (int) ($response['total'] ?? count($remoteByEmail));
            if (count($remoteByEmail) >= $total) {
                break;
            }

            $page++;
        }

        DB::transaction(function () use ($list, $remoteByEmail): void {
            $emails = array_keys($remoteByEmail);

            User::query()
                ->whereIn(DB::raw('LOWER(email)'), $emails === [] ? [''] : $emails)
                ->get(['id', 'email'])
                ->each(function (User $user) use ($list, $remoteByEmail): void {
                    $key = strtolower((string) $user->email);
                    $row = $remoteByEmail[$key] ?? null;
                    if ($row === null) {
                        return;
                    }

                    $list->subscribers()->syncWithoutDetaching([
                        $user->id => [
                            'listmonk_subscriber_id' => $row['listmonk_subscriber_id'] === 0 ? null : $row['listmonk_subscriber_id'],
                            'status' => $row['status']->value,
                            'last_synced_at' => now(),
                        ],
                    ]);
                });

            $list->subscribers()
                ->wherePivot('status', SubscriptionStatus::Enabled->value)
                ->whereNotIn(DB::raw('LOWER(users.email)'), $emails === [] ? [''] : $emails)
                ->each(function (User $user) use ($list): void {
                    $list->subscribers()->updateExistingPivot($user->id, [
                        'status' => SubscriptionStatus::Unsubscribed->value,
                        'last_synced_at' => now(),
                    ]);
                });

            $list->forceFill(['last_synced_at' => now()])->save();
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
