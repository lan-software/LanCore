<?php

use App\Domain\Event\Enums\EventStatus;
use App\Domain\Event\Models\Event;
use App\Domain\Notification\Models\NotificationPreference;
use App\Domain\Ticketing\Enums\TicketSalePhase;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketType;
use App\Domain\Ticketing\Notifications\TicketSaleNotification;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;

it('dispatches release notification to opted-in users for newly-released ticket types', function () {
    Notification::fake();

    $event = Event::factory()->create(['status' => EventStatus::Published]);
    $type = TicketType::factory()->create([
        'event_id' => $event->id,
        'is_hidden' => false,
        'purchase_from' => now()->subMinute(),
        'purchase_until' => now()->addDays(7),
        'notify_on_release' => true,
        'release_notified_at' => null,
    ]);

    $optedIn = User::factory()->create();
    NotificationPreference::factory()->create([
        'user_id' => $optedIn->id,
        'mail_on_ticket_sale' => true,
    ]);

    $optedOut = User::factory()->create();
    NotificationPreference::factory()->create([
        'user_id' => $optedOut->id,
        'mail_on_ticket_sale' => false,
        'push_on_ticket_sale' => false,
    ]);

    Artisan::call('notifications:dispatch-ticket-sale');
    Artisan::call('queue:work', ['--stop-when-empty' => true]);

    Notification::assertSentTo($optedIn, TicketSaleNotification::class, function ($notification) use ($type) {
        return $notification->phase === TicketSalePhase::Release
            && $notification->ticketType->is($type);
    });
    Notification::assertNotSentTo($optedOut, TicketSaleNotification::class);

    expect($type->fresh()->release_notified_at)->not->toBeNull();
});

it('is idempotent — running the dispatcher twice does not re-fire', function () {
    Notification::fake();

    $event = Event::factory()->create(['status' => EventStatus::Published]);
    $type = TicketType::factory()->create([
        'event_id' => $event->id,
        'is_hidden' => false,
        'purchase_from' => now()->subMinute(),
        'purchase_until' => now()->addDays(7),
        'notify_on_release' => true,
    ]);

    $user = User::factory()->create();
    NotificationPreference::factory()->create([
        'user_id' => $user->id,
        'mail_on_ticket_sale' => true,
    ]);

    Artisan::call('notifications:dispatch-ticket-sale');
    Artisan::call('queue:work', ['--stop-when-empty' => true]);
    Artisan::call('notifications:dispatch-ticket-sale');
    Artisan::call('queue:work', ['--stop-when-empty' => true]);

    Notification::assertSentToTimes($user, TicketSaleNotification::class, 1);
});

it('suppresses release notification for users already holding a ticket of that type', function () {
    Notification::fake();

    $event = Event::factory()->create(['status' => EventStatus::Published]);
    $type = TicketType::factory()->create([
        'event_id' => $event->id,
        'is_hidden' => false,
        'purchase_from' => now()->subMinute(),
        'purchase_until' => now()->addDays(7),
        'notify_on_release' => true,
    ]);

    $holder = User::factory()->create();
    NotificationPreference::factory()->create([
        'user_id' => $holder->id,
        'mail_on_ticket_sale' => true,
    ]);
    Ticket::factory()->create([
        'owner_id' => $holder->id,
        'ticket_type_id' => $type->id,
        'event_id' => $event->id,
    ]);

    $other = User::factory()->create();
    NotificationPreference::factory()->create([
        'user_id' => $other->id,
        'mail_on_ticket_sale' => true,
    ]);

    Artisan::call('notifications:dispatch-ticket-sale');
    Artisan::call('queue:work', ['--stop-when-empty' => true]);

    Notification::assertNotSentTo($holder, TicketSaleNotification::class);
    Notification::assertSentTo($other, TicketSaleNotification::class);
});

it('fires end-window notification once the lead time crosses', function () {
    Notification::fake();

    $event = Event::factory()->create(['status' => EventStatus::Published]);
    $type = TicketType::factory()->create([
        'event_id' => $event->id,
        'is_hidden' => false,
        'purchase_from' => now()->subDays(7),
        'purchase_until' => now()->addMinutes(30),
        'notify_on_end' => true,
        'notify_on_end_lead_minutes' => 60,
        'end_notified_at' => null,
    ]);

    $user = User::factory()->create();
    NotificationPreference::factory()->create([
        'user_id' => $user->id,
        'mail_on_ticket_sale' => true,
    ]);

    Artisan::call('notifications:dispatch-ticket-sale');
    Artisan::call('queue:work', ['--stop-when-empty' => true]);

    Notification::assertSentTo($user, TicketSaleNotification::class, function ($notification) {
        return $notification->phase === TicketSalePhase::End;
    });
    expect($type->fresh()->end_notified_at)->not->toBeNull();
});

it('skips sold-out / hidden ticket types as a race protection', function () {
    Notification::fake();

    $event = Event::factory()->create(['status' => EventStatus::Published]);
    $type = TicketType::factory()->create([
        'event_id' => $event->id,
        'is_hidden' => true,
        'purchase_from' => now()->subMinute(),
        'purchase_until' => now()->addDays(7),
        'notify_on_release' => true,
    ]);

    $user = User::factory()->create();
    NotificationPreference::factory()->create([
        'user_id' => $user->id,
        'mail_on_ticket_sale' => true,
    ]);

    Artisan::call('notifications:dispatch-ticket-sale');
    Artisan::call('queue:work', ['--stop-when-empty' => true]);

    Notification::assertNotSentTo($user, TicketSaleNotification::class);
    expect($type->fresh()->release_notified_at)->not->toBeNull();
});
