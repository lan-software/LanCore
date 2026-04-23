<?php

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function (): void {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
});

it('redirects to profile settings with a flash alert when adding to cart without a complete profile', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    expect($user->hasCompleteProfile())->toBeFalse();

    $this->actingAs($user)
        ->post(route('cart.add-item'), [
            'purchasable_type' => 'ticket_type',
            'purchasable_id' => 1,
            'event_id' => 1,
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('profileAlert', __('shop.cart.profile_incomplete'));
});

it('passes the profileAlert flash to the settings page as an Inertia prop', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->withSession(['profileAlert' => __('shop.cart.profile_incomplete')])
        ->get(route('profile.edit'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('settings/Profile')
            ->where('profileAlert', __('shop.cart.profile_incomplete'))
        );
});

it('does not redirect users with a complete profile away from cart.add-item', function () {
    $user = User::factory()->withRole(RoleName::User)->withCompleteProfile()->create();

    expect($user->hasCompleteProfile())->toBeTrue();

    $response = $this->actingAs($user)
        ->post(route('cart.add-item'), [
            'purchasable_type' => 'ticket_type',
            'purchasable_id' => 1,
            'event_id' => 1,
        ]);

    $response->assertSessionMissing('profileAlert');
    expect($response->headers->get('Location'))->not->toBe(route('profile.edit'));
});
