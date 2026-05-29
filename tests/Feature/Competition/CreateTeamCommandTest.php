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

it('fails when the competition does not exist', function (): void {
    $captain = User::factory()->withRole(RoleName::User)->create();

    $this->artisan('competitions:create-team', [
        'competition' => '999999',
        'captain' => (string) $captain->id,
        '--name' => 'Team Rocket',
    ])->expectsOutputToContain('not found')->assertFailed();
});

it('fails when the captain cannot be resolved', function (): void {
    $competition = Competition::factory()->registrationOpen()->create();

    $this->artisan('competitions:create-team', [
        'competition' => (string) $competition->id,
        'captain' => 'nobody@example.com',
        '--name' => 'Team Rocket',
    ])->expectsOutputToContain("Captain 'nobody@example.com' not found")->assertFailed();
});

it('fails when no name is provided', function (): void {
    $captain = User::factory()->withRole(RoleName::User)->create();
    $competition = Competition::factory()->registrationOpen()->create();

    $this->artisan('competitions:create-team', [
        'competition' => (string) $competition->id,
        'captain' => (string) $captain->id,
    ])->expectsOutputToContain('--name is required.')->assertFailed();
});

it('creates a team during open registration', function (): void {
    $captain = User::factory()->withRole(RoleName::User)->create();
    $competition = Competition::factory()->registrationOpen()->create();

    $this->artisan('competitions:create-team', [
        'competition' => (string) $competition->id,
        'captain' => (string) $captain->id,
        '--name' => 'Blasenmacher',
        '--tag' => 'BLAS',
    ])->expectsOutputToContain('created under competition')->assertSuccessful();

    $team = CompetitionTeam::where('competition_id', $competition->id)->where('name', 'Blasenmacher')->first();
    expect($team)->not->toBeNull()
        ->and($team->tag)->toBe('BLAS')
        ->and($team->captain_user_id)->toBe($captain->id)
        ->and(CompetitionTeamMember::where('team_id', $team->id)->where('user_id', $captain->id)->whereNull('left_at')->exists())->toBeTrue();
});

it('resolves the captain by email', function (): void {
    $captain = User::factory()->withRole(RoleName::User)->create(['email' => 'cap@example.com']);
    $competition = Competition::factory()->registrationOpen()->create();

    $this->artisan('competitions:create-team', [
        'competition' => (string) $competition->id,
        'captain' => 'cap@example.com',
        '--name' => 'Email Team',
    ])->assertSuccessful();

    expect(CompetitionTeam::where('name', 'Email Team')->value('captain_user_id'))->toBe($captain->id);
});

it('rejects creation when signup rules are unmet and suggests --force', function (): void {
    $captain = User::factory()->withRole(RoleName::User)->create(['steam_id_64' => null]);
    // A signup rule requiring a linked Steam account the captain does not have.
    $competition = Competition::factory()->registrationOpen()->create([
        'signup_rules' => [
            'type' => 'all',
            'rules' => [
                ['type' => 'rule', 'key' => 'has_steam_account_linked'],
            ],
        ],
    ]);

    $this->artisan('competitions:create-team', [
        'competition' => (string) $competition->id,
        'captain' => (string) $captain->id,
        '--name' => 'Rejected Team',
    ])
        ->expectsOutputToContain('Signup rules rejected the team creation:')
        ->expectsOutputToContain('Re-run with --force to bypass.')
        ->assertFailed();

    expect(CompetitionTeam::where('name', 'Rejected Team')->exists())->toBeFalse();
});

it('bypasses signup rules with --force', function (): void {
    $captain = User::factory()->withRole(RoleName::User)->create();
    $competition = Competition::factory()->create(['status' => 'draft']);

    $this->artisan('competitions:create-team', [
        'competition' => (string) $competition->id,
        'captain' => (string) $captain->id,
        '--name' => 'Forced Team',
        '--force' => true,
    ])->assertSuccessful();

    $team = CompetitionTeam::where('name', 'Forced Team')->first();
    expect($team)->not->toBeNull()
        ->and(CompetitionTeamMember::where('team_id', $team->id)->where('user_id', $captain->id)->exists())->toBeTrue();
});
