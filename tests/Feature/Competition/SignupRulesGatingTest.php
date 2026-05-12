<?php

use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\CompetitionTeam;
use App\Domain\Games\Models\Game;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
});

it('blocks team creation when a signup rule is unmet', function () {
    $user = User::factory()->withRole(RoleName::User)->create([
        'steam_id_64' => null,
    ]);
    $competition = Competition::factory()->registrationOpen()->create([
        'signup_rules' => [
            'type' => 'all',
            'rules' => [
                ['type' => 'rule', 'key' => 'has_steam_account_linked'],
            ],
        ],
    ]);

    $this->actingAs($user)
        ->post("/competitions/{$competition->id}/teams", [
            'name' => 'Team Rocket',
        ])
        ->assertSessionHasErrors('signup_rules');

    expect(CompetitionTeam::where('competition_id', $competition->id)->exists())
        ->toBeFalse();
});

it('allows team creation when the signup rule is satisfied', function () {
    $user = User::factory()->withRole(RoleName::User)->create([
        'steam_id_64' => '76561198000000000',
    ]);
    $competition = Competition::factory()->registrationOpen()->create([
        'signup_rules' => [
            'type' => 'all',
            'rules' => [
                ['type' => 'rule', 'key' => 'has_steam_account_linked'],
            ],
        ],
    ]);

    $this->actingAs($user)
        ->post("/competitions/{$competition->id}/teams", [
            'name' => 'Team Rocket',
        ])
        ->assertRedirect();

    expect(CompetitionTeam::where('competition_id', $competition->id)->exists())
        ->toBeTrue();
});

it('blocks joining a team when rule is unmet', function () {
    $user = User::factory()->withRole(RoleName::User)->create([
        'steam_id_64' => null,
    ]);
    $competition = Competition::factory()->registrationOpen()->create([
        'team_size' => 5,
        'signup_rules' => [
            'type' => 'all',
            'rules' => [
                ['type' => 'rule', 'key' => 'has_steam_account_linked'],
            ],
        ],
    ]);
    $team = CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
    ]);

    $this->actingAs($user)
        ->post("/competitions/{$competition->id}/teams/{$team->id}/join")
        ->assertSessionHasErrors('signup_rules');

    expect($team->activeMembers()->where('user_id', $user->id)->exists())
        ->toBeFalse();
});

it('falls back to game-level rules when competition has none', function () {
    $game = Game::factory()->create([
        'signup_rules' => [
            'type' => 'all',
            'rules' => [
                ['type' => 'rule', 'key' => 'has_steam_account_linked'],
            ],
        ],
    ]);
    $user = User::factory()->withRole(RoleName::User)->create([
        'steam_id_64' => null,
    ]);
    $competition = Competition::factory()->registrationOpen()->create([
        'game_id' => $game->id,
        'signup_rules' => null,
    ]);

    $this->actingAs($user)
        ->post("/competitions/{$competition->id}/teams", [
            'name' => 'Team Rocket',
        ])
        ->assertSessionHasErrors('signup_rules');
});

it('exposes signupGate on the user competition show page', function () {
    $user = User::factory()->withRole(RoleName::User)->create([
        'steam_id_64' => null,
    ]);
    $competition = Competition::factory()->registrationOpen()->create([
        'signup_rules' => [
            'type' => 'all',
            'rules' => [
                ['type' => 'rule', 'key' => 'has_steam_account_linked'],
            ],
        ],
    ]);

    $this->actingAs($user)
        ->get("/portal/competitions/{$competition->id}")
        ->assertInertia(
            fn ($page) => $page
                ->component('competitions/user/Show')
                ->where('signupGate.allowed', false)
                ->has('signupGate.unmetReasons', 1),
        );
});
