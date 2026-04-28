<?php

namespace App\Domain\Notification\Gdpr;

use App\Domain\Notification\Models\NotificationPreference;
use App\Domain\Notification\Models\ProgramNotificationSubscription;
use App\Domain\Notification\Models\PushSubscription;
use App\Domain\Policy\Gdpr\Contracts\GdprDataSource;
use App\Domain\Policy\Gdpr\GdprDataSourceResult;
use App\Domain\Policy\Gdpr\GdprExportContext;
use App\Models\User;

class NotificationDataSource implements GdprDataSource
{
    public function key(): string
    {
        return 'notifications';
    }

    public function label(): string
    {
        return 'Notification preferences and push subscriptions';
    }

    public function for(User $user, GdprExportContext $context): GdprDataSourceResult
    {
        $preferences = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->orderBy('id')
            ->get()
            ->map->attributesToArray()
            ->all();

        $programSubscriptions = ProgramNotificationSubscription::query()
            ->where('user_id', $user->id)
            ->orderBy('id')
            ->get()
            ->map->attributesToArray()
            ->all();

        $pushSubscriptions = PushSubscription::query()
            ->where('user_id', $user->id)
            ->orderBy('id')
            ->get()
            ->map(function (PushSubscription $sub): array {
                $row = $sub->attributesToArray();

                if (isset($row['endpoint']) && is_string($row['endpoint'])) {
                    $row['endpoint'] = self::partiallyRedact($row['endpoint']);
                }

                unset($row['public_key'], $row['auth_token']);

                return $row;
            })
            ->all();

        return new GdprDataSourceResult([
            'preferences' => $preferences,
            'program_subscriptions' => $programSubscriptions,
            'push_subscriptions' => $pushSubscriptions,
        ]);
    }

    private static function partiallyRedact(string $endpoint): string
    {
        if (mb_strlen($endpoint) <= 20) {
            return mb_substr($endpoint, 0, 6).'…';
        }

        return mb_substr($endpoint, 0, 24).'…'.mb_substr($endpoint, -8);
    }
}
