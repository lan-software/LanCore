<?php

namespace App\Domain\Notification\Channels;

use App\Domain\Notification\Support\WebPushFactory;
use Illuminate\Notifications\Notification;
use Minishlink\WebPush\Subscription;

/**
 * Bridges Laravel notifications to the existing VAPID push transport.
 *
 * Notifications opt in by adding `'webpush'` to their `via()` array and
 * implementing `toWebPush($notifiable): array` returning {title, body, url}.
 *
 * @see docs/mil-std-498/SSDD.md §5.14
 * @see docs/mil-std-498/SDD.md §5.13
 * @see docs/mil-std-498/SRS.md NTF-F-011
 */
class WebPushChannel
{
    public function __construct(private readonly WebPushFactory $factory) {}

    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWebPush')) {
            return;
        }

        if (! method_exists($notifiable, 'pushSubscriptions')) {
            return;
        }

        $payload = $notification->toWebPush($notifiable);
        $subscriptions = $notifiable->pushSubscriptions()->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $webpush = $this->factory->make();

        foreach ($subscriptions as $row) {
            $report = $webpush->sendOneNotification(
                Subscription::create([
                    'endpoint' => $row->endpoint,
                    'keys' => [
                        'p256dh' => $row->public_key,
                        'auth' => $row->auth_token,
                    ],
                    'contentEncoding' => $row->content_encoding ?? 'aesgcm',
                ]),
                json_encode($payload),
            );

            if (! $report->isSuccess()) {
                $statusCode = $report->getResponse()?->getStatusCode();

                if (in_array($statusCode, [404, 410], true)) {
                    $row->delete();
                }
            }
        }
    }
}
