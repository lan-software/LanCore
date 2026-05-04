<?php

use App\Domain\Newsletter\Enums\SubscriptionStatus;
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

it('forbids non-admins from changing another user subscriptions', function (): void {
    $actor = User::factory()->withRole(RoleName::User)->create(['email_verified_at' => now()]);
    $target = User::factory()->create(['email_verified_at' => now()]);
    NewsletterList::factory()->userSelectable()->create();

    $this->actingAs($actor)
        ->patch("/users/{$target->id}/newsletter-subscriptions", [
            'subscribed_list_ids' => [],
        ])
        ->assertForbidden();
});

it('lets an admin subscribe a managed user to a curated list', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create(['email_verified_at' => now()]);
    $target = User::factory()->create(['email_verified_at' => now()]);
    $list = NewsletterList::factory()->userSelectable()->create(['listmonk_id' => 77]);

    Http::fake([
        'listmonk.test/api/subscribers*' => Http::sequence()
            ->push(['data' => ['results' => [], 'total' => 0, 'page' => 1, 'per_page' => 1]], 200)
            ->push(['data' => ['id' => 50, 'email' => $target->email, 'lists' => []]], 200),
    ]);

    $this->actingAs($admin)
        ->patch("/users/{$target->id}/newsletter-subscriptions", [
            'subscribed_list_ids' => [$list->id],
        ])
        ->assertRedirect();

    expect($list->fresh()->subscribers()->where('users.id', $target->id)->first()?->pivot->status)
        ->toBe(SubscriptionStatus::Enabled);
});

it('renders the admin user show page with the newsletter tab wiring', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create(['email_verified_at' => now()]);
    $target = User::factory()->create(['email_verified_at' => now()]);
    NewsletterList::factory()->userSelectable()->create(['name' => 'Pickable']);

    $this->actingAs($admin)
        ->get("/users/{$target->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('users/Show'));
});
