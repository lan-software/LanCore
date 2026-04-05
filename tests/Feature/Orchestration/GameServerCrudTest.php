<?php

use App\Domain\Games\Models\Game;
use App\Domain\Orchestration\Enums\GameServerAllocationType;
use App\Domain\Orchestration\Enums\GameServerStatus;
use App\Domain\Orchestration\Models\GameServer;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows admins to view the game servers index', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/game-servers')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('orchestration/servers/Index'));
});

it('allows admins to view the create game server page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/game-servers/create')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('orchestration/servers/Create'));
});

it('allows admins to store a new game server', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $game = Game::factory()->create();

    $this->actingAs($admin)
        ->post('/game-servers', [
            'name' => 'CS2 Server #1',
            'host' => '192.168.1.100',
            'port' => 27015,
            'game_id' => $game->id,
            'allocation_type' => 'competition',
            'credentials' => ['rcon_password' => 'secret123'],
        ])
        ->assertRedirect('/game-servers');

    $server = GameServer::where('name', 'CS2 Server #1')->first();
    expect($server)->not->toBeNull();
    expect($server->status)->toBe(GameServerStatus::Available);
    expect($server->allocation_type)->toBe(GameServerAllocationType::Competition);
    expect($server->game_id)->toBe($game->id);
});

it('validates required fields when storing a game server', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/game-servers', [])
        ->assertSessionHasErrors(['name', 'host', 'port', 'game_id', 'allocation_type']);
});

it('allows admins to update a game server', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $server = GameServer::factory()->create();

    $this->actingAs($admin)
        ->patch("/game-servers/{$server->id}", [
            'name' => 'Updated Server',
        ])
        ->assertRedirect();

    expect($server->fresh()->name)->toBe('Updated Server');
});

it('allows admins to delete a game server', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $server = GameServer::factory()->create();

    $this->actingAs($admin)
        ->delete("/game-servers/{$server->id}")
        ->assertRedirect('/game-servers');

    expect(GameServer::find($server->id))->toBeNull();
});

it('prevents deleting an in-use server', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $server = GameServer::factory()->inUse()->create();

    $this->actingAs($admin)
        ->delete("/game-servers/{$server->id}")
        ->assertSessionHasErrors('server');

    expect(GameServer::find($server->id))->not->toBeNull();
});

it('forbids regular users from managing game servers', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/game-servers')
        ->assertForbidden();

    $this->actingAs($user)
        ->get('/game-servers/create')
        ->assertForbidden();
});
