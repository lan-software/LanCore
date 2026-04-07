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

it('sets left_at on the membership when a non-captain user leaves the team and redirects to my-competitions.show', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $captain = User::factory()->create();
    $competition = Competition::factory()->registrationOpen()->create();
    $team = CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
        'captain_user_id' => $captain->id,
    ]);
    CompetitionTeamMember::factory()->create([
        'team_id' => $team->id,
        'user_id' => $captain->id,
        'joined_at' => now()->subDays(5),
    ]);
    $membership = CompetitionTeamMember::factory()->create([
        'team_id' => $team->id,
        'user_id' => $user->id,
        'joined_at' => now()->subDay(),
    ]);

    $response = $this->actingAs($user)
        ->post("/competitions/{$competition->id}/teams/{$team->id}/leave");

    $response->assertRedirect(route('my-competitions.show', $competition));
    $response->assertSessionHas('success', 'You have left the team.');

    expect($membership->fresh()->left_at)->not->toBeNull();
    expect($team->activeMembers()->where('user_id', $user->id)->exists())->toBeFalse();
    expect($team->fresh()->captain_user_id)->toBe($captain->id);
});

it('rotates captaincy to the oldest active member when the captain leaves a team with members remaining', function () {
    $captain = User::factory()->withRole(RoleName::User)->create();
    $oldest = User::factory()->create();
    $newest = User::factory()->create();
    $competition = Competition::factory()->registrationOpen()->create();
    $team = CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
        'captain_user_id' => $captain->id,
    ]);

    CompetitionTeamMember::factory()->create([
        'team_id' => $team->id,
        'user_id' => $captain->id,
        'joined_at' => now()->subDays(10),
    ]);
    CompetitionTeamMember::factory()->create([
        'team_id' => $team->id,
        'user_id' => $oldest->id,
        'joined_at' => now()->subDays(8),
    ]);
    CompetitionTeamMember::factory()->create([
        'team_id' => $team->id,
        'user_id' => $newest->id,
        'joined_at' => now()->subDays(2),
    ]);

    $response = $this->actingAs($captain)
        ->post("/competitions/{$competition->id}/teams/{$team->id}/leave");

    $response->assertRedirect(route('my-competitions.show', $competition));
    $response->assertSessionHas('success', 'You have left the team.');

    $team->refresh();
    expect($team)->not->toBeNull();
    expect($team->captain_user_id)->toBe($oldest->id);
});

it('deletes the team when the last member (captain) leaves and flashes the disbanded message', function () {
    $captain = User::factory()->withRole(RoleName::User)->create();
    $competition = Competition::factory()->registrationOpen()->create();
    $team = CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
        'captain_user_id' => $captain->id,
    ]);
    CompetitionTeamMember::factory()->create([
        'team_id' => $team->id,
        'user_id' => $captain->id,
        'joined_at' => now()->subDay(),
    ]);

    $response = $this->actingAs($captain)
        ->post("/competitions/{$competition->id}/teams/{$team->id}/leave");

    $response->assertRedirect(route('my-competitions.show', $competition));
    $response->assertSessionHas('success', 'You left the team. As the last member, the team has been disbanded.');

    expect(CompetitionTeam::find($team->id))->toBeNull();
});

it('forbids leaving a team for a user who is not a member', function () {
    $stranger = User::factory()->withRole(RoleName::User)->create();
    $captain = User::factory()->create();
    $competition = Competition::factory()->registrationOpen()->create();
    $team = CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
        'captain_user_id' => $captain->id,
    ]);
    CompetitionTeamMember::factory()->create([
        'team_id' => $team->id,
        'user_id' => $captain->id,
        'joined_at' => now()->subDay(),
    ]);

    $this->actingAs($stranger)
        ->post("/competitions/{$competition->id}/teams/{$team->id}/leave")
        ->assertForbidden();
});

it('allows the captain to destroy the team via TeamController@destroy', function () {
    $captain = User::factory()->withRole(RoleName::User)->create();
    $competition = Competition::factory()->registrationOpen()->create();
    $team = CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
        'captain_user_id' => $captain->id,
    ]);
    CompetitionTeamMember::factory()->create([
        'team_id' => $team->id,
        'user_id' => $captain->id,
        'joined_at' => now()->subDay(),
    ]);

    $response = $this->actingAs($captain)
        ->delete("/competitions/{$competition->id}/teams/{$team->id}");

    $response->assertRedirect(route('my-competitions.show', $competition));
    $response->assertSessionHas('success', 'Team deleted.');

    expect(CompetitionTeam::find($team->id))->toBeNull();
});

it('forbids non-captain from destroying the team', function () {
    $captain = User::factory()->create();
    $member = User::factory()->withRole(RoleName::User)->create();
    $competition = Competition::factory()->registrationOpen()->create();
    $team = CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
        'captain_user_id' => $captain->id,
    ]);
    CompetitionTeamMember::factory()->create([
        'team_id' => $team->id,
        'user_id' => $captain->id,
        'joined_at' => now()->subDay(),
    ]);
    CompetitionTeamMember::factory()->create([
        'team_id' => $team->id,
        'user_id' => $member->id,
        'joined_at' => now()->subHour(),
    ]);

    $this->actingAs($member)
        ->delete("/competitions/{$competition->id}/teams/{$team->id}")
        ->assertForbidden();

    expect(CompetitionTeam::find($team->id))->not->toBeNull();
});
