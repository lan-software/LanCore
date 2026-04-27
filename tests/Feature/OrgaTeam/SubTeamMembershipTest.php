<?php

use App\Domain\OrgaTeam\Enums\SubTeamRole;
use App\Domain\OrgaTeam\Models\OrgaSubTeam;
use App\Domain\OrgaTeam\Models\OrgaTeam;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
});

it('admins can create a sub-team under an orga-team', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $team = OrgaTeam::factory()->create();

    $this->actingAs($admin)
        ->post("/orga-teams/{$team->id}/sub-teams", [
            'name' => 'Tech',
            'emoji' => '🛠',
            'color' => '#22d3ee',
            'leader_user_id' => User::factory()->create()->id,
        ])
        ->assertRedirect();

    $sub = OrgaSubTeam::firstWhere('name', 'Tech');
    expect($sub)->not->toBeNull();
    expect($sub->orga_team_id)->toBe($team->id);
    expect($sub->emoji)->toBe('🛠');
    expect($sub->color)->toBe('#22d3ee');
});

it('admins can update a sub-team', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $sub = OrgaSubTeam::factory()->create();

    $this->actingAs($admin)
        ->patch("/orga-sub-teams/{$sub->id}", [
            'name' => 'Renamed Sub',
            'sort_order' => 9,
        ])
        ->assertRedirect();

    expect($sub->fresh()->name)->toBe('Renamed Sub');
    expect($sub->fresh()->sort_order)->toBe(9);
});

it('admins can sync sub-team memberships with mixed roles', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $sub = OrgaSubTeam::factory()->create();
    $u1 = User::factory()->create();
    $u2 = User::factory()->create();
    $u3 = User::factory()->create();

    $this->actingAs($admin)
        ->patch("/orga-sub-teams/{$sub->id}/members", [
            'memberships' => [
                ['user_id' => $u1->id, 'role' => 'deputy'],
                ['user_id' => $u2->id, 'role' => 'member'],
                ['user_id' => $u3->id, 'role' => 'member'],
            ],
        ])
        ->assertRedirect();

    $sub->refresh();
    expect($sub->memberships)->toHaveCount(3);
    expect($sub->deputies()->count())->toBe(1);
    expect($sub->members()->count())->toBe(2);
});

it('membership sync prunes removed users', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $sub = OrgaSubTeam::factory()->create();
    $u1 = User::factory()->create();
    $u2 = User::factory()->create();

    $sub->users()->attach([
        $u1->id => ['role' => SubTeamRole::Member->value, 'sort_order' => 0],
        $u2->id => ['role' => SubTeamRole::Member->value, 'sort_order' => 1],
    ]);

    $this->actingAs($admin)
        ->patch("/orga-sub-teams/{$sub->id}/members", [
            'memberships' => [
                ['user_id' => $u1->id, 'role' => 'member'],
            ],
        ])
        ->assertRedirect();

    expect($sub->fresh()->memberships)->toHaveCount(1);
    expect($sub->fresh()->memberships->first()->user_id)->toBe($u1->id);
});

it('forbids users from managing sub-teams', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $sub = OrgaSubTeam::factory()->create();

    $this->actingAs($user)
        ->patch("/orga-sub-teams/{$sub->id}", ['name' => 'X'])
        ->assertForbidden();
});
