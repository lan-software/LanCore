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

it('sets left_at on the membership when a user leaves the team and redirects to my-competitions.show', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $competition = Competition::factory()->registrationOpen()->create();
    $team = CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
        'captain_user_id' => User::factory()->create()->id,
    ]);
    $membership = CompetitionTeamMember::factory()->create([
        'team_id' => $team->id,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)
        ->post("/competitions/{$competition->id}/teams/{$team->id}/leave");

    $response->assertRedirect(route('my-competitions.show', $competition));
    $response->assertSessionHas('success');

    expect($membership->fresh()->left_at)->not->toBeNull();
    expect($team->activeMembers()->where('user_id', $user->id)->exists())->toBeFalse();
});
