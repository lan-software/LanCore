<?php

namespace App\Domain\Notification\Listeners;

use App\Domain\Notification\Events\NotificationReceived;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Events\NotificationSent;

/**
 * Central live-delivery hook. Whenever any notification is persisted to the
 * database (via the `database` channel), we broadcast a single
 * `NotificationReceived` event on the user's private channel — no per-
 * notification class wiring needed.
 *
 * @see docs/mil-std-498/SRS.md NOT-F-021
 */
class BroadcastDatabaseNotification
{
    public function handle(NotificationSent $event): void
    {
        if ($event->channel !== 'database') {
            return;
        }

        if (! $event->notifiable instanceof User) {
            return;
        }

        $response = $event->response;

        if (! $response instanceof DatabaseNotification) {
            return;
        }

        NotificationReceived::dispatch(
            $event->notifiable->id,
            $response->id,
            $response->type ?? $event->notification::class,
            (array) ($response->data ?? []),
            $response->created_at?->toIso8601String(),
        );
    }
}
