<?php

use App\Domain\Event\Enums\EventStatus;
use App\Domain\Event\Models\Event;
use App\Domain\Notification\Models\NotificationPreference;
use App\Domain\Notification\Models\PushSubscription;
use App\Domain\Notification\Support\WebPushFactory;
use App\Domain\Ticketing\Enums\TicketSalePhase;
use App\Domain\Ticketing\Models\TicketType;
use App\Domain\Ticketing\Notifications\TicketSaleNotification;
use App\Models\User;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Notification;
use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\WebPush;

it('iterates push subscriptions and prunes 410-Gone endpoints', function () {
    $event = Event::factory()->create(['status' => EventStatus::Published]);
    $type = TicketType::factory()->create([
        'event_id' => $event->id,
        'is_hidden' => false,
        'purchase_from' => now()->subMinute(),
        'purchase_until' => now()->addDays(7),
    ]);

    $user = User::factory()->create();
    NotificationPreference::factory()->create([
        'user_id' => $user->id,
        'mail_on_ticket_sale' => false,
        'push_on_ticket_sale' => true,
    ]);

    $aliveSub = PushSubscription::create([
        'user_id' => $user->id,
        'endpoint' => 'https://fcm.googleapis.com/alive',
        'public_key' => str_repeat('a', 88),
        'auth_token' => str_repeat('b', 22),
        'content_encoding' => 'aes128gcm',
    ]);

    $deadSub = PushSubscription::create([
        'user_id' => $user->id,
        'endpoint' => 'https://fcm.googleapis.com/dead',
        'public_key' => str_repeat('c', 88),
        'auth_token' => str_repeat('d', 22),
        'content_encoding' => 'aes128gcm',
    ]);

    $captured = [];
    $webpush = Mockery::mock(WebPush::class);
    $webpush->shouldReceive('sendOneNotification')
        ->twice()
        ->andReturnUsing(function ($subscription, $payload) use (&$captured) {
            $captured[] = ['endpoint' => $subscription->getEndpoint(), 'payload' => $payload];
            $isDead = str_ends_with($subscription->getEndpoint(), 'dead');

            return new MessageSentReport(
                new Request('POST', $subscription->getEndpoint()),
                $isDead ? new Response(410) : new Response(201),
                ! $isDead,
            );
        });

    $factory = Mockery::mock(WebPushFactory::class);
    $factory->shouldReceive('make')->once()->andReturn($webpush);
    $this->app->instance(WebPushFactory::class, $factory);

    Notification::send($user, new TicketSaleNotification($type, TicketSalePhase::Release));

    expect($captured)->toHaveCount(2);
    expect(PushSubscription::find($aliveSub->id))->not->toBeNull();
    expect(PushSubscription::find($deadSub->id))->toBeNull();
});
