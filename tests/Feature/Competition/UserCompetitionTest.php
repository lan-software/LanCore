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

it('shows only competitions where user is a team member', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $myCompetition = Competition::factory()->create();
    $team = CompetitionTeam::factory()->create(['competition_id' => $myCompetition->id]);
    CompetitionTeamMember::factory()->create(['team_id' => $team->id, 'user_id' => $user->id]);

    $otherCompetition = Competition::factory()->create();

    $this->actingAs($user)
        ->get('/my-competitions')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('competitions/user/Index')
                ->has('competitions.data', 1)
                ->where('competitions.data.0.id', $myCompetition->id)
        );
});

it('allows user to view their competition', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $competition = Competition::factory()->create();
    $team = CompetitionTeam::factory()->create(['competition_id' => $competition->id]);
    CompetitionTeamMember::factory()->create(['team_id' => $team->id, 'user_id' => $user->id]);

    $this->actingAs($user)
        ->get("/my-competitions/{$competition->id}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('competitions/user/Show'));
});

it('forbids user from viewing competition they are not part of', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $competition = Competition::factory()->create();

    $this->actingAs($user)
        ->get("/my-competitions/{$competition->id}")
        ->assertForbidden();
});
