<?php

use App\Domain\Announcement\Events\AnnouncementPublished;
use App\Domain\Announcement\Listeners\HandleAnnouncementPublishedWebhooks;
use App\Domain\Announcement\Models\Announcement;
use App\Domain\Event\Actions\UpdateEvent;
use App\Domain\Event\Enums\EventStatus;
use App\Domain\Event\Events\EventPublished;
use App\Domain\Event\Listeners\HandleEventPublishedWebhooks;
use App\Domain\Event\Models\Event;
use App\Domain\News\Events\NewsArticlePublished;
use App\Domain\News\Listeners\HandleNewsArticlePublishedWebhooks;
use App\Domain\News\Models\NewsArticle;
use App\Domain\Webhook\Actions\CreateWebhook;
use App\Domain\Webhook\Actions\UpdateWebhook;
use App\Domain\Webhook\Enums\WebhookEvent;
use App\Domain\Webhook\Events\WebhookDispatched;
use App\Domain\Webhook\Listeners\HandleUserRegisteredWebhooks;
use App\Domain\Webhook\Listeners\SendWebhookPayload;
use App\Domain\Webhook\Models\Webhook;
use App\Domain\Webhook\Models\WebhookDelivery;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
});

// --- Action Tests ---

it('creates a webhook', function () {
    $action = app(CreateWebhook::class);
    $webhook = $action->execute([
        'name' => 'My Webhook',
        'url' => 'https://example.com/webhook',
        'event' => WebhookEvent::UserRegistered->value,
        'is_active' => true,
    ]);

    expect($webhook)->toBeInstanceOf(Webhook::class)
        ->and($webhook->name)->toBe('My Webhook')
        ->and($webhook->event)->toBe(WebhookEvent::UserRegistered)
        ->and($webhook->is_active)->toBeTrue();
});

it('updates a webhook', function () {
    $webhook = Webhook::factory()->create(['name' => 'Old Name']);

    $action = app(UpdateWebhook::class);
    $action->execute($webhook, ['name' => 'New Name']);

    expect($webhook->fresh()->name)->toBe('New Name');
});

// --- Event / Listener Tests ---

it('dispatches WebhookDispatched for each active webhook when user registers', function () {
    EventFacade::fake([WebhookDispatched::class]);

    $activeWebhook = Webhook::factory()->create([
        'event' => WebhookEvent::UserRegistered->value,
        'is_active' => true,
    ]);
    $inactiveWebhook = Webhook::factory()->inactive()->create([
        'event' => WebhookEvent::UserRegistered->value,
    ]);

    $user = User::factory()->create();
    $listener = app(HandleUserRegisteredWebhooks::class);
    $listener->handle(new Registered($user));

    EventFacade::assertDispatched(WebhookDispatched::class, fn ($event) => $event->webhook->id === $activeWebhook->id);
    EventFacade::assertNotDispatched(WebhookDispatched::class, fn ($event) => $event->webhook->id === $inactiveWebhook->id);
});

it('includes user data in webhook payload', function () {
    EventFacade::fake([WebhookDispatched::class]);

    Webhook::factory()->create([
        'event' => WebhookEvent::UserRegistered->value,
        'is_active' => true,
    ]);

    $user = User::factory()->create();
    $listener = app(HandleUserRegisteredWebhooks::class);
    $listener->handle(new Registered($user));

    EventFacade::assertDispatched(WebhookDispatched::class, function ($event) use ($user) {
        return $event->payload['event'] === WebhookEvent::UserRegistered->value
            && $event->payload['user']['id'] === $user->id
            && $event->payload['user']['email'] === $user->email;
    });
});

it('does not dispatch WebhookDispatched when no active webhooks exist', function () {
    EventFacade::fake([WebhookDispatched::class]);

    $user = User::factory()->create();
    $listener = app(HandleUserRegisteredWebhooks::class);
    $listener->handle(new Registered($user));

    EventFacade::assertNotDispatched(WebhookDispatched::class);
});

it('dispatches WebhookDispatched for active webhooks when announcement is published', function () {
    EventFacade::fake([WebhookDispatched::class]);

    $activeWebhook = Webhook::factory()->create([
        'event' => WebhookEvent::AnnouncementPublished->value,
        'is_active' => true,
    ]);
    $inactiveWebhook = Webhook::factory()->inactive()->create([
        'event' => WebhookEvent::AnnouncementPublished->value,
    ]);

    $announcement = Announcement::factory()->create();
    $listener = app(HandleAnnouncementPublishedWebhooks::class);
    $listener->handle(new AnnouncementPublished($announcement));

    EventFacade::assertDispatched(WebhookDispatched::class, fn ($event) => $event->webhook->id === $activeWebhook->id);
    EventFacade::assertNotDispatched(WebhookDispatched::class, fn ($event) => $event->webhook->id === $inactiveWebhook->id);
});

it('includes announcement data in webhook payload', function () {
    EventFacade::fake([WebhookDispatched::class]);

    Webhook::factory()->create([
        'event' => WebhookEvent::AnnouncementPublished->value,
        'is_active' => true,
    ]);

    $announcement = Announcement::factory()->create();
    $listener = app(HandleAnnouncementPublishedWebhooks::class);
    $listener->handle(new AnnouncementPublished($announcement));

    EventFacade::assertDispatched(WebhookDispatched::class, function ($event) use ($announcement) {
        return $event->payload['event'] === WebhookEvent::AnnouncementPublished->value
            && $event->payload['announcement']['id'] === $announcement->id
            && $event->payload['announcement']['title'] === $announcement->title;
    });
});

it('dispatches WebhookDispatched for active webhooks when news article is published', function () {
    EventFacade::fake([WebhookDispatched::class]);

    $activeWebhook = Webhook::factory()->create([
        'event' => WebhookEvent::NewsArticlePublished->value,
        'is_active' => true,
    ]);

    $article = NewsArticle::factory()->create();
    $listener = app(HandleNewsArticlePublishedWebhooks::class);
    $listener->handle(new NewsArticlePublished($article));

    EventFacade::assertDispatched(WebhookDispatched::class, fn ($event) => $event->webhook->id === $activeWebhook->id);
});

it('includes news article data in webhook payload', function () {
    EventFacade::fake([WebhookDispatched::class]);

    Webhook::factory()->create([
        'event' => WebhookEvent::NewsArticlePublished->value,
        'is_active' => true,
    ]);

    $article = NewsArticle::factory()->create();
    $listener = app(HandleNewsArticlePublishedWebhooks::class);
    $listener->handle(new NewsArticlePublished($article));

    EventFacade::assertDispatched(WebhookDispatched::class, function ($event) use ($article) {
        return $event->payload['event'] === WebhookEvent::NewsArticlePublished->value
            && $event->payload['article']['id'] === $article->id
            && $event->payload['article']['slug'] === $article->slug;
    });
});

it('dispatches EventPublished when event status transitions to published', function () {
    EventFacade::fake([EventPublished::class]);

    $lanEvent = Event::factory()->create(['status' => EventStatus::Draft]);

    $action = app(UpdateEvent::class);
    $action->execute($lanEvent, ['status' => EventStatus::Published->value]);

    EventFacade::assertDispatched(EventPublished::class, fn ($event) => $event->event->id === $lanEvent->id);
});

it('does not dispatch EventPublished when event is already published', function () {
    EventFacade::fake([EventPublished::class]);

    $lanEvent = Event::factory()->create(['status' => EventStatus::Published]);

    $action = app(UpdateEvent::class);
    $action->execute($lanEvent, ['name' => 'New Name']);

    EventFacade::assertNotDispatched(EventPublished::class);
});

it('dispatches WebhookDispatched for active webhooks when event is published', function () {
    EventFacade::fake([WebhookDispatched::class]);

    $activeWebhook = Webhook::factory()->create([
        'event' => WebhookEvent::EventPublished->value,
        'is_active' => true,
    ]);

    $lanEvent = Event::factory()->create();
    $listener = app(HandleEventPublishedWebhooks::class);
    $listener->handle(new EventPublished($lanEvent));

    EventFacade::assertDispatched(WebhookDispatched::class, fn ($event) => $event->webhook->id === $activeWebhook->id);
});

it('includes event data in webhook payload', function () {
    EventFacade::fake([WebhookDispatched::class]);

    Webhook::factory()->create([
        'event' => WebhookEvent::EventPublished->value,
        'is_active' => true,
    ]);

    $lanEvent = Event::factory()->create();
    $listener = app(HandleEventPublishedWebhooks::class);
    $listener->handle(new EventPublished($lanEvent));

    EventFacade::assertDispatched(WebhookDispatched::class, function ($event) use ($lanEvent) {
        return $event->payload['event'] === WebhookEvent::EventPublished->value
            && $event->payload['lan_event']['id'] === $lanEvent->id
            && $event->payload['lan_event']['name'] === $lanEvent->name;
    });
});

// --- HTTP Admin CRUD Tests ---

it('redirects unauthenticated users from webhooks index', function () {
    $this->get(route('webhooks.index'))->assertRedirect(route('login'));
});

it('forbids non-admin users from viewing webhooks', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $this->actingAs($user)->get(route('webhooks.index'))->assertForbidden();
});

it('allows admins to view webhooks index', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    Webhook::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('webhooks.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('webhooks/Index')->has('webhooks'));
});

it('allows admins to create a webhook', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post(route('webhooks.store'), [
            'name' => 'Test Hook',
            'url' => 'https://example.com/hook',
            'event' => WebhookEvent::UserRegistered->value,
            'is_active' => true,
        ])
        ->assertRedirect(route('webhooks.index'));

    expect(Webhook::where('name', 'Test Hook')->exists())->toBeTrue();
});

it('validates webhook url format', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post(route('webhooks.store'), [
            'name' => 'Bad Webhook',
            'url' => 'not-a-url',
            'event' => WebhookEvent::UserRegistered->value,
        ])
        ->assertSessionHasErrors(['url']);
});

it('allows admins to update a webhook', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $webhook = Webhook::factory()->create(['name' => 'Old Name']);

    $this->actingAs($admin)
        ->patch(route('webhooks.update', $webhook), [
            'name' => 'Updated Name',
            'url' => $webhook->url,
            'event' => $webhook->event->value,
            'is_active' => $webhook->is_active,
        ])
        ->assertRedirect();

    expect($webhook->fresh()->name)->toBe('Updated Name');
});

it('allows admins to delete a webhook', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $webhook = Webhook::factory()->create();

    $this->actingAs($admin)
        ->delete(route('webhooks.destroy', $webhook))
        ->assertRedirect(route('webhooks.index'));

    expect(Webhook::find($webhook->id))->toBeNull();
});

it('sends HTTP POST to webhook URL when WebhookDispatched is handled', function () {
    Http::fake();

    $webhook = Webhook::factory()->create([
        'url' => 'https://example.com/hook',
        'secret' => 'my-secret',
    ]);

    $payload = ['event' => 'user.registered', 'user' => ['id' => 1, 'name' => 'Test']];

    $listener = app(SendWebhookPayload::class);
    $listener->handle(new WebhookDispatched($webhook, $payload));

    Http::assertSent(fn ($request) => $request->url() === 'https://example.com/hook'
        && $request->hasHeader('X-Webhook-Event', $webhook->event->value)
        && $request->hasHeader('X-Webhook-Signature'));
});

it('logs a successful delivery when webhook payload is sent', function () {
    Http::fake(['*' => Http::response(null, 200)]);

    $webhook = Webhook::factory()->create(['url' => 'https://example.com/hook']);
    $listener = app(SendWebhookPayload::class);
    $listener->handle(new WebhookDispatched($webhook, ['event' => 'user.registered']));

    $delivery = WebhookDelivery::where('webhook_id', $webhook->id)->sole();
    expect($delivery->succeeded)->toBeTrue()
        ->and($delivery->status_code)->toBe(200)
        ->and($delivery->duration_ms)->not->toBeNull()
        ->and($delivery->fired_at)->not->toBeNull()
        ->and($webhook->fresh()->sent_count)->toBe(1);
});

it('logs a failed delivery when the endpoint returns an error', function () {
    Http::fake(['*' => Http::response(null, 500)]);

    $webhook = Webhook::factory()->create(['url' => 'https://example.com/hook']);
    $listener = app(SendWebhookPayload::class);

    expect(fn () => $listener->handle(new WebhookDispatched($webhook, ['event' => 'user.registered'])))
        ->toThrow(RequestException::class);

    $delivery = WebhookDelivery::where('webhook_id', $webhook->id)->sole();
    expect($delivery->succeeded)->toBeFalse()
        ->and($delivery->status_code)->toBe(500)
        ->and($webhook->fresh()->sent_count)->toBe(1);
});

it('allows admins to view webhook show page with delivery history', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $webhook = Webhook::factory()->create();

    $this->actingAs($admin)
        ->get(route('webhooks.show', $webhook))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('webhooks/Show')
            ->has('webhook')
            ->has('deliveries')
        );
});
