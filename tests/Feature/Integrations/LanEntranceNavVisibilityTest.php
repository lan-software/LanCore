<?php

use App\Domain\Integration\Models\IntegrationApp;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function (): void {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Moderator->value], ['label' => 'Moderator']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);

    IntegrationApp::factory()->create([
        'name' => 'LanEntrance',
        'slug' => 'lanentrance',
        'nav_url' => 'http://lanentrance.test',
        'nav_icon' => 'door-open',
        'nav_label' => 'Entrance',
        'is_active' => true,
    ]);
});

it('hides the LanEntrance nav entry from guests', function (): void {
    $this->get('/login')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('integrationLinks', fn ($links) => collect($links)->every(
                fn ($link) => $link['icon'] !== 'door-open'
            ))
        );
});

it('hides the LanEntrance nav entry from regular users', function (): void {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('integrationLinks', fn ($links) => collect($links)->every(
                fn ($link) => $link['icon'] !== 'door-open'
            ))
        );
});

it('shows the LanEntrance nav entry to moderators', function (): void {
    $mod = User::factory()->withRole(RoleName::Moderator)->create();

    $this->actingAs($mod)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('integrationLinks', fn ($links) => collect($links)->contains(
                fn ($link) => $link['icon'] === 'door-open'
                    && $link['url'] === 'http://lanentrance.test'
                    && $link['label'] === 'Entrance'
            ))
        );
});

it('shows the LanEntrance nav entry to admins', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('integrationLinks', fn ($links) => collect($links)->contains(
                fn ($link) => $link['icon'] === 'door-open'
            ))
        );
});

it('shows the LanEntrance nav entry to superadmins', function (): void {
    $superadmin = User::factory()->withRole(RoleName::Superadmin)->create();

    $this->actingAs($superadmin)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('integrationLinks', fn ($links) => collect($links)->contains(
                fn ($link) => $link['icon'] === 'door-open'
            ))
        );
});
