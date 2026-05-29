<?php

use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\CompetitionTeam;
use App\Domain\Competition\Models\CompetitionTeamMember;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function (): void {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
});

it('fails when the team does not exist', function (): void {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->artisan('competitions:add-team-member', [
        'team' => '999999',
        'user' => (string) $user->id,
    ])->expectsOutputToContain('not found')->assertFailed();
});

it('fails when the user cannot be resolved', function (): void {
    $competition = Competition::factory()->registrationOpen()->create();
    $team = CompetitionTeam::factory()->create(['competition_id' => $competition->id]);

    $this->artisan('competitions:add-team-member', [
        'team' => (string) $team->id,
        'user' => 'ghost@example.com',
    ])->expectsOutputToContain("User 'ghost@example.com' not found")->assertFailed();
});

it('warns and succeeds when the user is already an active member', function (): void {
    $competition = Competition::factory()->registrationOpen()->create();
    $team = CompetitionTeam::factory()->create(['competition_id' => $competition->id]);
    $user = User::factory()->withRole(RoleName::User)->create();
    CompetitionTeamMember::create([
        'team_id' => $team->id,
        'user_id' => $user->id,
        'joined_at' => now(),
    ]);

    $this->artisan('competitions:add-team-member', [
        'team' => (string) $team->id,
        'user' => (string) $user->id,
    ])->expectsOutputToContain('is already an active member')->assertSuccessful();

    expect(CompetitionTeamMember::where('team_id', $team->id)->where('user_id', $user->id)->whereNull('left_at')->count())->toBe(1);
});

it('adds a member during open registration', function (): void {
    $competition = Competition::factory()->registrationOpen()->create(['team_size' => 5]);
    $captain = User::factory()->withRole(RoleName::User)->create();
    $team = CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
        'captain_user_id' => $captain->id,
    ]);
    CompetitionTeamMember::create(['team_id' => $team->id, 'user_id' => $captain->id, 'joined_at' => now()]);

    $newMember = User::factory()->withRole(RoleName::User)->create();

    $this->artisan('competitions:add-team-member', [
        'team' => (string) $team->id,
        'user' => (string) $newMember->id,
    ])->expectsOutputToContain('joined team')->assertSuccessful();

    expect(CompetitionTeamMember::where('team_id', $team->id)->where('user_id', $newMember->id)->whereNull('left_at')->exists())->toBeTrue();
});

it('bypasses signup rules with --force', function (): void {
    $competition = Competition::factory()->create(['status' => 'draft']);
    $team = CompetitionTeam::factory()->create(['competition_id' => $competition->id]);
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->artisan('competitions:add-team-member', [
        'team' => (string) $team->id,
        'user' => (string) $user->id,
        '--force' => true,
    ])->assertSuccessful();

    expect(CompetitionTeamMember::where('team_id', $team->id)->where('user_id', $user->id)->exists())->toBeTrue();
});
