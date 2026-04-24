<?php

use App\Domain\Shop\Models\ShopSetting;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
});

it('exposes the currency and available currencies to the shop settings page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    ShopSetting::set('currency', 'usd');

    $this->actingAs($admin)
        ->get('/shop-settings')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('shop/Settings')
            ->where('currency', 'usd')
            ->has('availableCurrencies', 4)
            ->where('availableCurrencies.0.value', 'eur')
            ->where('availableCurrencies.0.symbol', '€')
        );
});

it('allows authenticated users to update the shop currency', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->patch('/shop-settings/currency', ['currency' => 'usd'])
        ->assertRedirect();

    expect(ShopSetting::currency())->toBe('usd');
});

it('rejects unknown currency codes', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->patch('/shop-settings/currency', ['currency' => 'xyz'])
        ->assertSessionHasErrors('currency');

    expect(ShopSetting::currency())->not->toBe('xyz');
});

it('rejects unauthenticated currency updates', function () {
    $this->patch('/shop-settings/currency', ['currency' => 'usd'])
        ->assertRedirect('/login');
});

it('normalises mixed-case currency input to lowercase', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->patch('/shop-settings/currency', ['currency' => 'EUR'])
        ->assertRedirect();

    expect(ShopSetting::get('currency'))->toBe('eur');
});
