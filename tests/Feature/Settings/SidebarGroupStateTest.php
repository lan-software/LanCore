<?php

use App\Models\User;

test('unauthenticated users cannot toggle sidebar group state', function () {
    $this->postJson(route('sidebar-groups.toggle'), ['group_id' => 'platform'])
        ->assertUnauthorized();
});

test('user can collapse a sidebar group', function () {
    $user = User::factory()->create(['sidebar_collapsed_groups' => null]);

    $this->actingAs($user)
        ->post(route('sidebar-groups.toggle'), ['group_id' => 'platform'])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $user->refresh();
    expect($user->sidebar_collapsed_groups)->toBe(['platform']);
});

test('toggling a collapsed group reopens it', function () {
    $user = User::factory()->create([
        'sidebar_collapsed_groups' => ['platform', 'shop'],
    ]);

    $this->actingAs($user)
        ->post(route('sidebar-groups.toggle'), ['group_id' => 'platform'])
        ->assertRedirect();

    $user->refresh();
    expect($user->sidebar_collapsed_groups)->toBe(['shop']);
});

test('toggle requires a valid group_id', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('sidebar-groups.toggle'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('group_id');
});

test('group_id must be a string with max 100 characters', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('sidebar-groups.toggle'), ['group_id' => str_repeat('a', 101)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('group_id');
});

test('sidebar collapsed groups are shared via inertia', function () {
    $user = User::factory()->create([
        'sidebar_collapsed_groups' => ['platform', 'integrations'],
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page->where(
                'sidebarCollapsedGroups',
                ['platform', 'integrations'],
            ),
        );
});

test('sidebar collapsed groups defaults to empty array for new users', function () {
    $user = User::factory()->create(['sidebar_collapsed_groups' => null]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->where('sidebarCollapsedGroups', []));
});
