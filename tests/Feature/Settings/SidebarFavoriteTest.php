<?php

use App\Models\User;

test('unauthenticated users cannot toggle sidebar favorites', function () {
    $this->postJson(route('sidebar-favorites.toggle'), ['item_id' => 'users'])
        ->assertUnauthorized();
});

test('user can add a sidebar favorite', function () {
    $user = User::factory()->create(['sidebar_favorites' => null]);

    $this->actingAs($user)
        ->post(route('sidebar-favorites.toggle'), ['item_id' => 'users'])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $user->refresh();
    expect($user->sidebar_favorites)->toBe(['users']);
});

test('user can remove a sidebar favorite', function () {
    $user = User::factory()->create(['sidebar_favorites' => ['users', 'orders']]);

    $this->actingAs($user)
        ->post(route('sidebar-favorites.toggle'), ['item_id' => 'users'])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $user->refresh();
    expect($user->sidebar_favorites)->toBe(['orders']);
});

test('user can toggle multiple sidebar favorites', function () {
    $user = User::factory()->create(['sidebar_favorites' => null]);

    $this->actingAs($user)
        ->post(route('sidebar-favorites.toggle'), ['item_id' => 'users'])
        ->assertRedirect();

    $this->actingAs($user)
        ->post(route('sidebar-favorites.toggle'), ['item_id' => 'orders'])
        ->assertRedirect();

    $user->refresh();
    expect($user->sidebar_favorites)->toBe(['users', 'orders']);
});

test('toggle requires a valid item_id', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('sidebar-favorites.toggle'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('item_id');
});

test('item_id must be a string with max 100 characters', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('sidebar-favorites.toggle'), ['item_id' => str_repeat('a', 101)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('item_id');
});

test('sidebar favorites are shared via inertia', function () {
    $user = User::factory()->create(['sidebar_favorites' => ['users', 'orders']]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->where('sidebarFavorites', ['users', 'orders']));
});

test('sidebar favorites defaults to empty array for new users', function () {
    $user = User::factory()->create(['sidebar_favorites' => null]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->where('sidebarFavorites', []));
});
