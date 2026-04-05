<?php

use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\CompetitionTeam;
use App\Domain\Competition\Models\CompetitionTeamMember;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
});

it('allows user to create a team during registration', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $competition = Competition::factory()->registrationOpen()->create();

    $this->actingAs($user)
        ->post("/competitions/{$competition->id}/teams", [
            'name' => 'Team Rocket',
            'tag' => 'TR',
        ])
        ->assertRedirect();

    $team = CompetitionTeam::where('competition_id', $competition->id)->where('name', 'Team Rocket')->first();
    expect($team)->not->toBeNull();
    expect($team->captain_user_id)->toBe($user->id);
    expect($team->activeMembers()->where('user_id', $user->id)->exists())->toBeTrue();
});

it('forbids team creation when registration is not open', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $competition = Competition::factory()->create(['status' => 'draft']);

    $this->actingAs($user)
        ->post("/competitions/{$competition->id}/teams", [
            'name' => 'Team Rocket',
        ])
        ->assertForbidden();
});

it('forbids creating a second team in the same competition', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $competition = Competition::factory()->registrationOpen()->create();
    $team = CompetitionTeam::factory()->create(['competition_id' => $competition->id]);
    CompetitionTeamMember::factory()->create(['team_id' => $team->id, 'user_id' => $user->id]);

    $this->actingAs($user)
        ->post("/competitions/{$competition->id}/teams", [
            'name' => 'Second Team',
        ])
        ->assertForbidden();
});

it('allows user to join an existing team', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $competition = Competition::factory()->registrationOpen()->create(['team_size' => 5]);
    $team = CompetitionTeam::factory()->create(['competition_id' => $competition->id]);

    $this->actingAs($user)
        ->post("/competitions/{$competition->id}/teams/{$team->id}/join")
        ->assertRedirect();

    expect($team->activeMembers()->where('user_id', $user->id)->exists())->toBeTrue();
});

it('allows user to leave a team', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $competition = Competition::factory()->registrationOpen()->create();
    $team = CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
        'captain_user_id' => User::factory()->create()->id,
    ]);
    CompetitionTeamMember::factory()->create(['team_id' => $team->id, 'user_id' => $user->id]);

    $this->actingAs($user)
        ->post("/competitions/{$competition->id}/teams/{$team->id}/leave")
        ->assertRedirect();

    expect($team->activeMembers()->where('user_id', $user->id)->exists())->toBeFalse();
});
