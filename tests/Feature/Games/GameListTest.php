<?php

use App\Domain\Games\Models\Game;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('returns paginated games for admins', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    Game::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get('/games')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('games/Index')
                ->has('games.data')
                ->has('games.total')
                ->has('filters')
        );
});

it('filters games by search term', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    Game::factory()->create(['name' => 'Counter-Strike 2']);
    Game::factory()->create(['name' => 'League of Legends']);

    $this->actingAs($admin)
        ->get('/games?search=counter')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('games/Index')
                ->where('games.total', 1)
                ->where('games.data.0.name', 'Counter-Strike 2')
        );
});

it('sorts games by name ascending', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    Game::factory()->create(['name' => 'Zeta Game']);
    Game::factory()->create(['name' => 'Alpha Game']);

    $this->actingAs($admin)
        ->get('/games?sort=name&direction=asc')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('games/Index')
                ->where('games.data.0.name', 'Alpha Game')
        );
});
