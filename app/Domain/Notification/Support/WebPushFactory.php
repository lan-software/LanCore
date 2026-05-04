<?php

namespace App\Domain\Notification\Support;

use Minishlink\WebPush\WebPush;

/**
 * @see docs/mil-std-498/SDD.md §5.13 — centralises VAPID-configured WebPush instantiation
 *      so that TestPushNotificationCommand and WebPushChannel build identical clients.
 * @see docs/mil-std-498/SRS.md NTF-F-011
 */
class WebPushFactory
{
    public function make(): WebPush
    {
        return new WebPush([
            'VAPID' => [
                'subject' => config('services.vapid.subject'),
                'publicKey' => config('services.vapid.public_key'),
                'privateKey' => config('services.vapid.private_key'),
            ],
        ]);
    }
}
