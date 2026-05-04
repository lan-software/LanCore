<?php

use App\Domain\Newsletter\Enums\SubscriptionStatus;
use App\Domain\Newsletter\Models\NewsletterList;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    config()->set('listmonk.enabled', true);
    config()->set('listmonk.base_url', 'https://listmonk.test');
    config()->set('listmonk.username', 'admin');
    config()->set('listmonk.password', 'secret');
});

it('shows curated lists to authenticated users', function (): void {
    $user = User::factory()->withRole(RoleName::User)->create(['email_verified_at' => now()]);
    NewsletterList::factory()->userSelectable()->create(['name' => 'Pickable']);
    NewsletterList::factory()->create(['name' => 'Hidden']);

    $this->actingAs($user)
        ->get('/settings/email')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/EmailSettings')
            ->where('lists.0.name', 'Pickable')
            ->count('lists', 1),
        );
});

it('subscribes a user when they tick a list and writes the pivot', function (): void {
    $user = User::factory()->withRole(RoleName::User)->create(['email_verified_at' => now()]);
    $list = NewsletterList::factory()->userSelectable()->create(['listmonk_id' => 42]);

    Http::fake([
        'listmonk.test/api/subscribers*' => Http::sequence()
            ->push(['data' => ['results' => [], 'total' => 0, 'page' => 1, 'per_page' => 1]], 200)
            ->push(['data' => ['id' => 9, 'email' => $user->email, 'lists' => []]], 200),
    ]);

    $this->actingAs($user)
        ->patch('/settings/email', [
            'subscribed_list_ids' => [$list->id],
        ])
        ->assertRedirect();

    expect($list->fresh()->subscribers()->where('users.id', $user->id)->first()?->pivot->status)
        ->toBe(SubscriptionStatus::Enabled);
});
