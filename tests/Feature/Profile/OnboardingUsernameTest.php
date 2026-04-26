<?php

use App\Models\User;

/**
 * @see docs/mil-std-498/STD.md §4.23 Username Tests (onboarding middleware)
 * @see docs/mil-std-498/SRS.md USR-F-022
 */
test('users without a username are redirected to onboarding', function () {
    $user = User::factory()->withoutUsername()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('onboarding.username.show'));
});

test('users with a username pass the onboarding middleware', function () {
    $user = User::factory()->create(['username' => 'phantom']);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
});

test('onboarding submission persists the username and redirects', function () {
    $user = User::factory()->withoutUsername()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('onboarding.username.update'), [
            'username' => 'shadow_runner',
        ]);

    $response->assertRedirect();
    expect($user->fresh()->username)->toBe('shadow_runner');
});

test('onboarding rejects an already-taken username', function () {
    User::factory()->create(['username' => 'phantom']);
    $user = User::factory()->withoutUsername()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('onboarding.username.update'), [
            'username' => 'PHANTOM',
        ]);

    $response->assertSessionHasErrors('username');
    expect($user->fresh()->username)->toBeNull();
});
