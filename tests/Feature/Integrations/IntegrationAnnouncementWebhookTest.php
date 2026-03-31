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

it('sets the webhook secret when provided', function () {
    $app = IntegrationApp::factory()->withAnnouncements()->create();

    app(SyncIntegrationWebhooks::class)->execute($app, 'my-secret');

    $webhook = Webhook::where('integration_app_id', $app->id)->sole();
    expect($webhook->secret)->toBe('my-secret');
});

it('updates the webhook secret when changed', function () {
    $app = IntegrationApp::factory()->withAnnouncements()->create();
    app(SyncIntegrationWebhooks::class)->execute($app, 'original-secret');

    app(SyncIntegrationWebhooks::class)->execute($app, 'new-secret');

    $webhook = Webhook::where('integration_app_id', $app->id)->sole();
    expect($webhook->secret)->toBe('new-secret');
});

it('clears the webhook secret when null is provided', function () {
    $app = IntegrationApp::factory()->withAnnouncements()->create();
    app(SyncIntegrationWebhooks::class)->execute($app, 'original-secret');

    app(SyncIntegrationWebhooks::class)->execute($app, null);

    $webhook = Webhook::where('integration_app_id', $app->id)->sole();
    expect($webhook->secret)->toBeNull();
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

it('sets managed webhook secret when storing integration with a secret', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/integrations', [
            'name' => 'LanShout',
            'slug' => 'lanshout',
            'is_active' => true,
            'send_announcements' => true,
            'announcement_endpoint' => 'https://lanshout.example.com/api/announcements',
            'announcement_webhook_secret' => 'super-secret',
        ])
        ->assertRedirect('/integrations');

    $app = IntegrationApp::where('slug', 'lanshout')->first();
    $webhook = Webhook::where('integration_app_id', $app->id)->sole();
    expect($webhook->secret)->toBe('super-secret');
});

it('updates managed webhook secret when updating integration', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $app = IntegrationApp::factory()->withAnnouncements()->create();
    app(SyncIntegrationWebhooks::class)->execute($app, 'old-secret');

    $this->actingAs($admin)
        ->patch("/integrations/{$app->id}", [
            'name' => $app->name,
            'send_announcements' => true,
            'announcement_endpoint' => $app->announcement_endpoint,
            'announcement_webhook_secret' => 'new-secret',
        ])
        ->assertRedirect();

    $webhook = Webhook::where('integration_app_id', $app->id)->sole();
    expect($webhook->secret)->toBe('new-secret');
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

/*
|--------------------------------------------------------------------------
| Managed Role Update Webhooks
|--------------------------------------------------------------------------
*/

it('creates a managed roles webhook when send_role_updates is enabled', function () {
    $app = IntegrationApp::factory()->withRoleUpdates()->create();

    app(SyncIntegrationWebhooks::class)->execute($app);

    $webhook = Webhook::where('integration_app_id', $app->id)
        ->where('event', WebhookEvent::UserRolesUpdated->value)
        ->sole();

    expect($webhook->url)->toBe('https://lanshout.example.com/api/webhooks/roles')
        ->and($webhook->is_active)->toBeTrue()
        ->and($webhook->isManaged())->toBeTrue()
        ->and($webhook->name)->toContain("Integration: {$app->name}");
});

it('does not create a roles webhook when send_role_updates is disabled', function () {
    $app = IntegrationApp::factory()->create(['send_role_updates' => false]);

    app(SyncIntegrationWebhooks::class)->execute($app);

    expect(Webhook::where('integration_app_id', $app->id)
        ->where('event', WebhookEvent::UserRolesUpdated->value)
        ->count())->toBe(0);
});

it('updates managed roles webhook when endpoint changes', function () {
    $app = IntegrationApp::factory()->withRoleUpdates()->create();
    app(SyncIntegrationWebhooks::class)->execute($app);

    $app->update(['roles_endpoint' => 'https://new-url.example.com/roles']);
    app(SyncIntegrationWebhooks::class)->execute($app);

    $webhooks = Webhook::where('integration_app_id', $app->id)
        ->where('event', WebhookEvent::UserRolesUpdated->value)
        ->get();

    expect($webhooks)->toHaveCount(1)
        ->and($webhooks->first()->url)->toBe('https://new-url.example.com/roles');
});

it('deletes managed roles webhook when send_role_updates is disabled', function () {
    $app = IntegrationApp::factory()->withRoleUpdates()->create();
    app(SyncIntegrationWebhooks::class)->execute($app);

    $app->update(['send_role_updates' => false]);
    app(SyncIntegrationWebhooks::class)->execute($app);

    expect(Webhook::where('integration_app_id', $app->id)
        ->where('event', WebhookEvent::UserRolesUpdated->value)
        ->count())->toBe(0);
});

it('sets the roles webhook secret when provided', function () {
    $app = IntegrationApp::factory()->withRoleUpdates()->create();

    app(SyncIntegrationWebhooks::class)->execute($app, null, 'roles-secret');

    $webhook = Webhook::where('integration_app_id', $app->id)
        ->where('event', WebhookEvent::UserRolesUpdated->value)
        ->sole();

    expect($webhook->secret)->toBe('roles-secret');
});

it('creates both announcement and roles webhooks independently', function () {
    $app = IntegrationApp::factory()->withAnnouncements()->withRoleUpdates()->create();

    app(SyncIntegrationWebhooks::class)->execute($app, 'ann-secret', 'roles-secret');

    $webhooks = Webhook::where('integration_app_id', $app->id)->get();
    expect($webhooks)->toHaveCount(2);

    $announcement = $webhooks->firstWhere('event', WebhookEvent::AnnouncementPublished);
    $roles = $webhooks->firstWhere('event', WebhookEvent::UserRolesUpdated);

    expect($announcement->secret)->toBe('ann-secret')
        ->and($roles->secret)->toBe('roles-secret');
});

it('allows admins to store an integration with role update settings via HTTP', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/integrations', [
            'name' => 'LanShout',
            'slug' => 'lanshout-roles',
            'is_active' => true,
            'send_role_updates' => true,
            'roles_endpoint' => 'https://lanshout.example.com/api/webhooks/roles',
            'roles_webhook_secret' => 'my-roles-secret',
        ])
        ->assertRedirect('/integrations');

    $app = IntegrationApp::where('slug', 'lanshout-roles')->first();
    expect($app->send_role_updates)->toBeTrue()
        ->and($app->roles_endpoint)->toBe('https://lanshout.example.com/api/webhooks/roles');

    $webhook = Webhook::where('integration_app_id', $app->id)
        ->where('event', WebhookEvent::UserRolesUpdated->value)
        ->sole();

    expect($webhook->secret)->toBe('my-roles-secret');
});

it('validates roles_endpoint is required when send_role_updates is true', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/integrations', [
            'name' => 'Test',
            'slug' => 'test-roles-val',
            'send_role_updates' => true,
            'roles_endpoint' => '',
        ])
        ->assertSessionHasErrors(['roles_endpoint']);
});
