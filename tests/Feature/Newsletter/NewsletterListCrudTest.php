<?php

use App\Domain\Newsletter\Models\NewsletterList;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);

    config()->set('listmonk.enabled', true);
    config()->set('listmonk.base_url', 'https://listmonk.test');
    config()->set('listmonk.username', 'admin');
    config()->set('listmonk.password', 'secret');
});

it('forbids regular users from listing newsletter lists', function (): void {
    $user = User::factory()->withRole(RoleName::User)->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->get('/backstage/newsletter-lists')
        ->assertForbidden();
});

it('lets admins list newsletter lists', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create(['email_verified_at' => now()]);
    NewsletterList::factory()->create(['name' => 'Demo']);

    $this->actingAs($admin)
        ->get('/backstage/newsletter-lists')
        ->assertOk();
});

it('creates a list both in Listmonk and the local mirror', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create(['email_verified_at' => now()]);

    Http::fake([
        'listmonk.test/api/lists' => Http::response(
            ['data' => ['id' => 77, 'name' => 'Hype Train', 'type' => 'public', 'optin' => 'single']],
            200,
        ),
    ]);

    $this->actingAs($admin)
        ->post('/backstage/newsletter-lists', [
            'name' => 'Hype Train',
            'description' => 'Big news only',
            'type' => 'public',
            'optin' => 'single',
            'is_user_selectable' => true,
            'is_default_public' => false,
        ])
        ->assertRedirect('/backstage/newsletter-lists');

    expect(NewsletterList::where('listmonk_id', 77)->first())
        ->not->toBeNull()
        ->and(NewsletterList::where('listmonk_id', 77)->first()->name)->toBe('Hype Train');
});

it('deletes a local mirror without deleting in Listmonk by default', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create(['email_verified_at' => now()]);
    $list = NewsletterList::factory()->create();

    Http::fake();

    $this->actingAs($admin)
        ->delete("/backstage/newsletter-lists/{$list->id}")
        ->assertRedirect('/backstage/newsletter-lists');

    expect(NewsletterList::find($list->id))->toBeNull();
    Http::assertNothingSent();
});
