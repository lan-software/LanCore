<?php

namespace App\Console\Commands\Notification;

use App\Domain\Notification\Models\PushSubscription;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

#[Signature('test:notification:push
    {--user= : User ID or email to send to (defaults to first subscribed user)}
    {--title= : Notification title}
    {--body= : Notification body}
    {--url= : URL to open when the notification is clicked}
')]
#[Description('Send a test web push notification to a subscribed user')]
class TestPushNotificationCommand extends Command
{
    public function handle(): int
    {
        $subscription = $this->resolveSubscription();

        if (! $subscription) {
            $this->error('No push subscription found. Subscribe in the browser first via /settings/notifications.');

            return self::FAILURE;
        }

        $title = $this->option('title') ?? 'Test push notification';
        $body = $this->option('body') ?? 'This is a test push sent from the console.';
        $url = $this->option('url') ?? '/notifications';

        $this->info("Sending push to: <comment>{$subscription->endpoint}</comment>");

        $webpush = new WebPush([
            'VAPID' => [
                'subject' => config('services.vapid.subject'),
                'publicKey' => config('services.vapid.public_key'),
                'privateKey' => config('services.vapid.private_key'),
            ],
        ]);

        $sub = Subscription::create([
            'endpoint' => $subscription->endpoint,
            'keys' => [
                'p256dh' => $subscription->public_key,
                'auth' => $subscription->auth_token,
            ],
            'contentEncoding' => $subscription->content_encoding ?? 'aesgcm',
        ]);

        $report = $webpush->sendOneNotification(
            $sub,
            json_encode([
                'title' => $title,
                'body' => $body,
                'url' => $url,
            ]),
        );

        if ($report->isSuccess()) {
            $this->info('Push notification sent successfully.');

            return self::SUCCESS;
        }

        $this->error("Failed to send push notification: {$report->getReason()}");

        return self::FAILURE;
    }

    private function resolveSubscription(): ?PushSubscription
    {
        $userOption = $this->option('user');

        if ($userOption) {
            $user = is_numeric($userOption)
                ? User::find((int) $userOption)
                : User::where('email', $userOption)->first();

            if (! $user) {
                $this->error("User '{$userOption}' not found.");

                return null;
            }

            return $user->pushSubscriptions()->latest()->first();
        }

        return PushSubscription::latest()->first();
    }
}
