<?php

use App\Domain\Games\Models\Game;
use App\Domain\Games\Models\GameMode;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows admins to view the create game page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/games/create')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('games/Create'));
});

it('allows admins to store a new game', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/games', [
            'name' => 'Counter-Strike 2',
            'slug' => 'counter-strike-2',
            'publisher' => 'Valve',
            'description' => 'Tactical FPS',
        ])
        ->assertRedirect('/games');

    expect(Game::where('name', 'Counter-Strike 2')->exists())->toBeTrue();
});

it('validates required fields when storing a game', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/games', [])
        ->assertSessionHasErrors(['name', 'slug']);
});

it('validates slug uniqueness when storing a game', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    Game::factory()->create(['slug' => 'existing-slug']);

    $this->actingAs($admin)
        ->post('/games', [
            'name' => 'New Game',
            'slug' => 'existing-slug',
        ])
        ->assertSessionHasErrors(['slug']);
});

it('allows admins to view the edit game page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $game = Game::factory()->create();

    $this->actingAs($admin)
        ->get("/games/{$game->id}")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('games/Edit')
                ->has('game')
                ->where('game.id', $game->id)
        );
});

it('allows admins to update a game', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $game = Game::factory()->create();

    $this->actingAs($admin)
        ->patch("/games/{$game->id}", [
            'name' => 'Updated Game',
            'slug' => $game->slug,
        ])
        ->assertRedirect();

    expect($game->fresh()->name)->toBe('Updated Game');
});

it('allows admins to delete a game', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $game = Game::factory()->create();

    $this->actingAs($admin)
        ->delete("/games/{$game->id}")
        ->assertRedirect('/games');

    expect(Game::find($game->id))->toBeNull();
});

it('cascades deletion to game modes when a game is deleted', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $game = Game::factory()->create();
    GameMode::factory()->count(2)->create(['game_id' => $game->id]);

    $this->actingAs($admin)
        ->delete("/games/{$game->id}")
        ->assertRedirect('/games');

    expect(GameMode::where('game_id', $game->id)->count())->toBe(0);
});

it('forbids users from creating games', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->post('/games', [
            'name' => 'Test',
            'slug' => 'test',
        ])
        ->assertForbidden();
});

// Game Mode Tests

it('allows admins to view the create game mode page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $game = Game::factory()->create();

    $this->actingAs($admin)
        ->get("/games/{$game->id}/modes/create")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('games/modes/Create'));
});

it('allows admins to store a new game mode', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $game = Game::factory()->create();

    $this->actingAs($admin)
        ->post("/games/{$game->id}/modes", [
            'name' => '5v5 Competitive',
            'slug' => '5v5-competitive',
            'team_size' => 5,
            'parameters' => json_encode(['map_pool' => ['dust2', 'mirage']]),
        ])
        ->assertRedirect("/games/{$game->id}");

    $mode = GameMode::where('name', '5v5 Competitive')->first();
    expect($mode)->not->toBeNull();
    expect($mode->game_id)->toBe($game->id);
    expect($mode->team_size)->toBe(5);
    expect($mode->parameters)->toBe(['map_pool' => ['dust2', 'mirage']]);
});

it('allows admins to update a game mode', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $game = Game::factory()->create();
    $mode = GameMode::factory()->create(['game_id' => $game->id]);

    $this->actingAs($admin)
        ->patch("/games/{$game->id}/modes/{$mode->id}", [
            'name' => 'Updated Mode',
            'slug' => $mode->slug,
            'team_size' => 3,
        ])
        ->assertRedirect();

    $mode->refresh();
    expect($mode->name)->toBe('Updated Mode');
    expect($mode->team_size)->toBe(3);
});

it('allows admins to delete a game mode', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $game = Game::factory()->create();
    $mode = GameMode::factory()->create(['game_id' => $game->id]);

    $this->actingAs($admin)
        ->delete("/games/{$game->id}/modes/{$mode->id}")
        ->assertRedirect("/games/{$game->id}");

    expect(GameMode::find($mode->id))->toBeNull();
});

it('validates required fields for game modes', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $game = Game::factory()->create();

    $this->actingAs($admin)
        ->post("/games/{$game->id}/modes", [])
        ->assertSessionHasErrors(['name', 'slug', 'team_size']);
});

it('allows storing a game mode with null parameters', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $game = Game::factory()->create();

    $this->actingAs($admin)
        ->post("/games/{$game->id}/modes", [
            'name' => 'Simple Mode',
            'slug' => 'simple-mode',
            'team_size' => 1,
        ])
        ->assertRedirect("/games/{$game->id}");

    $mode = GameMode::where('slug', 'simple-mode')->first();
    expect($mode->parameters)->toBeNull();
});
