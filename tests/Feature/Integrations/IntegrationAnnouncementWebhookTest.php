<?php

use App\Domain\Announcement\Events\AnnouncementPublished;
use App\Domain\Announcement\Listeners\HandleAnnouncementPublishedWebhooks;
use App\Domain\Announcement\Models\Announcement;
use App\Domain\Integration\Actions\CreateIntegrationApp;
use App\Domain\Integration\Actions\DeleteIntegrationApp;
use App\Domain\Integration\Actions\SyncIntegrationWebhooks;
use App\Domain\Integration\Actions\UpdateIntegrationApp;
use App\Domain\Integration\Models\IntegrationApp;
use App\Domain\Webhook\Enums\WebhookEvent;
use App\Domain\Webhook\Events\WebhookDispatched;
use App\Domain\Webhook\Models\Webhook;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Event as EventFacade;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

/*
|--------------------------------------------------------------------------
| SyncIntegrationWebhooks Action
|--------------------------------------------------------------------------
*/

it('creates a managed webhook when send_announcements is enabled', function () {
    $app = IntegrationApp::factory()->withAnnouncements()->create();

    app(SyncIntegrationWebhooks::class)->execute($app);

    $webhook = Webhook::where('integration_app_id', $app->id)->sole();
    expect($webhook->event)->toBe(WebhookEvent::AnnouncementPublished)
        ->and($webhook->url)->toBe('https://lanshout.example.com/api/announcements')
        ->and($webhook->is_active)->toBeTrue()
        ->and($webhook->isManaged())->toBeTrue()
        ->and($webhook->name)->toContain("Integration: {$app->name}");
});

it('does not create a webhook when send_announcements is disabled', function () {
    $app = IntegrationApp::factory()->create(['send_announcements' => false]);

    app(SyncIntegrationWebhooks::class)->execute($app);

    expect(Webhook::where('integration_app_id', $app->id)->count())->toBe(0);
});

it('updates an existing managed webhook when endpoint changes', function () {
    $app = IntegrationApp::factory()->withAnnouncements()->create();
    app(SyncIntegrationWebhooks::class)->execute($app);

    $app->update(['announcement_endpoint' => 'https://new-url.example.com/hook']);
    app(SyncIntegrationWebhooks::class)->execute($app);

    $webhooks = Webhook::where('integration_app_id', $app->id)->get();
    expect($webhooks)->toHaveCount(1)
        ->and($webhooks->first()->url)->toBe('https://new-url.example.com/hook');
});

it('deletes managed webhook when send_announcements is disabled', function () {
    $app = IntegrationApp::factory()->withAnnouncements()->create();
    app(SyncIntegrationWebhooks::class)->execute($app);

    expect(Webhook::where('integration_app_id', $app->id)->count())->toBe(1);

    $app->update(['send_announcements' => false]);
    app(SyncIntegrationWebhooks::class)->execute($app);

    expect(Webhook::where('integration_app_id', $app->id)->count())->toBe(0);
});

it('deactivates managed webhook when integration is deactivated', function () {
    $app = IntegrationApp::factory()->withAnnouncements()->create();
    app(SyncIntegrationWebhooks::class)->execute($app);

    $app->update(['is_active' => false]);
    app(SyncIntegrationWebhooks::class)->execute($app);

    expect(Webhook::where('integration_app_id', $app->id)->sole()->is_active)->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| CreateIntegrationApp action syncs webhooks
|--------------------------------------------------------------------------
*/

it('creates managed webhook when creating integration with announcements enabled', function () {
    $app = app(CreateIntegrationApp::class)->execute([
        'name' => 'LanShout',
        'slug' => 'lanshout',
        'send_announcements' => true,
        'announcement_endpoint' => 'https://lanshout.example.com/api/announcements',
        'is_active' => true,
    ]);

    expect(Webhook::where('integration_app_id', $app->id)->count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| UpdateIntegrationApp action syncs webhooks
|--------------------------------------------------------------------------
*/

it('creates managed webhook when updating integration to enable announcements', function () {
    $app = IntegrationApp::factory()->create();

    app(UpdateIntegrationApp::class)->execute($app, [
        'name' => $app->name,
        'send_announcements' => true,
        'announcement_endpoint' => 'https://lanshout.example.com/api/announcements',
    ]);

    expect(Webhook::where('integration_app_id', $app->id)->count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| DeleteIntegrationApp cleans up webhooks
|--------------------------------------------------------------------------
*/

it('deletes managed webhooks when integration is deleted', function () {
    $app = IntegrationApp::factory()->withAnnouncements()->create();
    app(SyncIntegrationWebhooks::class)->execute($app);

    expect(Webhook::where('integration_app_id', $app->id)->count())->toBe(1);

    app(DeleteIntegrationApp::class)->execute($app);

    expect(Webhook::where('integration_app_id', $app->id)->count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| WebhookPolicy protects managed webhooks
|--------------------------------------------------------------------------
*/

it('prevents admins from updating managed webhooks', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $app = IntegrationApp::factory()->withAnnouncements()->create();
    app(SyncIntegrationWebhooks::class)->execute($app);

    $webhook = Webhook::where('integration_app_id', $app->id)->sole();

    $this->actingAs($admin)
        ->patch(route('webhooks.update', $webhook), [
            'name' => 'Hacked',
            'url' => 'https://evil.com',
            'event' => WebhookEvent::AnnouncementPublished->value,
            'is_active' => true,
        ])
        ->assertForbidden();
});

it('prevents admins from deleting managed webhooks', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $app = IntegrationApp::factory()->withAnnouncements()->create();
    app(SyncIntegrationWebhooks::class)->execute($app);

    $webhook = Webhook::where('integration_app_id', $app->id)->sole();

    $this->actingAs($admin)
        ->delete(route('webhooks.destroy', $webhook))
        ->assertForbidden();

    expect(Webhook::find($webhook->id))->not->toBeNull();
});

it('allows superadmins to update managed webhooks', function () {
    $superadmin = User::factory()->withRole(RoleName::Superadmin)->create();
    $app = IntegrationApp::factory()->withAnnouncements()->create();
    app(SyncIntegrationWebhooks::class)->execute($app);

    $webhook = Webhook::where('integration_app_id', $app->id)->sole();

    $this->actingAs($superadmin)
        ->patch(route('webhooks.update', $webhook), [
            'name' => 'Updated by superadmin',
            'url' => $webhook->url,
            'event' => $webhook->event->value,
            'is_active' => true,
        ])
        ->assertRedirect();

    expect($webhook->fresh()->name)->toBe('Updated by superadmin');
});

/*
|--------------------------------------------------------------------------
| HTTP CRUD - Integration Announcement Fields
|--------------------------------------------------------------------------
*/

it('allows admins to store an integration with announcement settings', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/integrations', [
            'name' => 'LanShout',
            'slug' => 'lanshout',
            'is_active' => true,
            'send_announcements' => true,
            'announcement_endpoint' => 'https://lanshout.example.com/api/announcements',
        ])
        ->assertRedirect('/integrations');

    $app = IntegrationApp::where('slug', 'lanshout')->first();
    expect($app->send_announcements)->toBeTrue()
        ->and($app->announcement_endpoint)->toBe('https://lanshout.example.com/api/announcements');

    expect(Webhook::where('integration_app_id', $app->id)->count())->toBe(1);
});

it('validates announcement_endpoint is required when send_announcements is true', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/integrations', [
            'name' => 'Test',
            'slug' => 'test',
            'send_announcements' => true,
            'announcement_endpoint' => '',
        ])
        ->assertSessionHasErrors(['announcement_endpoint']);
});

it('validates announcement_endpoint must be a valid url', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/integrations', [
            'name' => 'Test',
            'slug' => 'test',
            'send_announcements' => true,
            'announcement_endpoint' => 'not-a-url',
        ])
        ->assertSessionHasErrors(['announcement_endpoint']);
});

it('allows admins to update integration announcement settings', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $app = IntegrationApp::factory()->create();

    $this->actingAs($admin)
        ->patch("/integrations/{$app->id}", [
            'name' => $app->name,
            'send_announcements' => true,
            'announcement_endpoint' => 'https://lanshout.example.com/api/announcements',
        ])
        ->assertRedirect();

    $app->refresh();
    expect($app->send_announcements)->toBeTrue()
        ->and($app->announcement_endpoint)->toBe('https://lanshout.example.com/api/announcements');
});

/*
|--------------------------------------------------------------------------
| Integration webhook is dispatched for announcements
|--------------------------------------------------------------------------
*/

it('dispatches managed integration webhook when announcement is published', function () {
    EventFacade::fake([WebhookDispatched::class]);

    $app = IntegrationApp::factory()->withAnnouncements()->create();
    app(SyncIntegrationWebhooks::class)->execute($app);

    $announcement = Announcement::factory()->create();
    $listener = app(HandleAnnouncementPublishedWebhooks::class);
    $listener->handle(new AnnouncementPublished($announcement));

    EventFacade::assertDispatched(WebhookDispatched::class, function ($event) use ($app) {
        return $event->webhook->integration_app_id === $app->id;
    });
});
