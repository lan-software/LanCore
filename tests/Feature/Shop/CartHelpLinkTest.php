<?php

use App\Domain\Integration\Models\IntegrationApp;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function (): void {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
});

it('shares the LanHelp life-buoy entry in integrationLinks when active', function () {
    IntegrationApp::factory()->create([
        'name' => 'LanHelp',
        'slug' => 'lanhelp',
        'nav_url' => 'http://lanhelp.test',
        'nav_icon' => 'life-buoy',
        'nav_label' => 'Help',
        'is_active' => true,
    ]);

    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/cart')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('cart/Index')
            ->where('integrationLinks', fn ($links) => collect($links)->contains(
                fn ($link) => $link['icon'] === 'life-buoy'
                    && $link['url'] === 'http://lanhelp.test'
                    && $link['label'] === 'Help'
            ))
        );
});

it('omits the LanHelp entry from integrationLinks when the app is inactive', function () {
    IntegrationApp::factory()->inactive()->create([
        'name' => 'LanHelp',
        'slug' => 'lanhelp',
        'nav_url' => 'http://lanhelp.test',
        'nav_icon' => 'life-buoy',
        'nav_label' => 'Help',
    ]);

    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/cart')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('cart/Index')
            ->where('integrationLinks', fn ($links) => collect($links)->every(
                fn ($link) => $link['icon'] !== 'life-buoy'
            ))
        );
});

it('omits the LanHelp entry when nav_url is null', function () {
    IntegrationApp::factory()->create([
        'name' => 'LanHelp',
        'slug' => 'lanhelp',
        'nav_url' => null,
        'nav_icon' => 'life-buoy',
        'nav_label' => 'Help',
        'is_active' => true,
    ]);

    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/cart')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('cart/Index')
            ->where('integrationLinks', fn ($links) => collect($links)->every(
                fn ($link) => $link['icon'] !== 'life-buoy'
            ))
        );
});
