<?php

use App\Domain\OrgaTeam\Models\OrgaSubTeam;
use App\Domain\OrgaTeam\Models\OrgaTeam;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
});

it('admins can create an orga-team with deputies', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $organizer = User::factory()->create();
    $deputies = User::factory()->count(2)->create();

    $this->actingAs($admin)
        ->post('/orga-teams', [
            'name' => 'SXLAN Crew',
            'slug' => 'sxlan-crew',
            'description' => 'The crew running SXLAN events.',
            'organizer_user_id' => $organizer->id,
            'deputy_user_ids' => $deputies->pluck('id')->all(),
        ])
        ->assertRedirect();

    $team = OrgaTeam::firstWhere('slug', 'sxlan-crew');
    expect($team)->not->toBeNull();
    expect($team->organizer_user_id)->toBe($organizer->id);
    expect($team->deputies)->toHaveCount(2);
});

it('rejects when organizer is also listed as a deputy', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $organizer = User::factory()->create();

    $this->actingAs($admin)
        ->post('/orga-teams', [
            'name' => 'Bad Crew',
            'slug' => 'bad-crew',
            'organizer_user_id' => $organizer->id,
            'deputy_user_ids' => [$organizer->id],
        ])
        ->assertSessionHasErrors('deputy_user_ids.0');
});

it('admins can update an orga-team and resync deputies', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $team = OrgaTeam::factory()->create();
    $newDeputy = User::factory()->create();

    $this->actingAs($admin)
        ->patch("/orga-teams/{$team->id}", [
            'name' => 'Renamed Crew',
            'slug' => $team->slug,
            'organizer_user_id' => $team->organizer_user_id,
            'deputy_user_ids' => [$newDeputy->id],
        ])
        ->assertRedirect();

    expect($team->fresh()->name)->toBe('Renamed Crew');
    expect($team->deputies()->pluck('users.id')->all())->toEqual([$newDeputy->id]);
});

it('admins can delete an orga-team and cascade sub-teams', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $team = OrgaTeam::factory()
        ->has(OrgaSubTeam::factory()->count(3), 'subTeams')
        ->create();

    expect($team->subTeams)->toHaveCount(3);

    $this->actingAs($admin)
        ->delete("/orga-teams/{$team->id}")
        ->assertRedirect('/orga-teams');

    expect(OrgaTeam::find($team->id))->toBeNull();
    expect(OrgaSubTeam::where('orga_team_id', $team->id)->count())->toBe(0);
});

it('rejects duplicate slugs', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $organizer = User::factory()->create();
    OrgaTeam::factory()->create(['slug' => 'taken-slug']);

    $this->actingAs($admin)
        ->post('/orga-teams', [
            'name' => 'Other Crew',
            'slug' => 'taken-slug',
            'organizer_user_id' => $organizer->id,
        ])
        ->assertSessionHasErrors('slug');
});
