<?php

use App\Domain\Competition\Actions\LeaveTeam;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\CompetitionTeam;
use App\Domain\Competition\Models\CompetitionTeamMember;
use App\Models\User;

it('returns false when a non-captain leaves and members remain', function () {
    $captain = User::factory()->create();
    $user = User::factory()->create();
    $competition = Competition::factory()->create();
    $team = CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
        'captain_user_id' => $captain->id,
    ]);
    CompetitionTeamMember::factory()->create([
        'team_id' => $team->id,
        'user_id' => $captain->id,
        'joined_at' => now()->subDays(3),
    ]);
    CompetitionTeamMember::factory()->create([
        'team_id' => $team->id,
        'user_id' => $user->id,
        'joined_at' => now()->subDay(),
    ]);

    $result = (new LeaveTeam)->execute($team, $user);

    expect($result)->toBeFalse();
    expect(CompetitionTeam::find($team->id))->not->toBeNull();
    expect($team->fresh()->captain_user_id)->toBe($captain->id);
});

it('returns true and deletes the team when the last member (captain) leaves', function () {
    $captain = User::factory()->create();
    $competition = Competition::factory()->create();
    $team = CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
        'captain_user_id' => $captain->id,
    ]);
    CompetitionTeamMember::factory()->create([
        'team_id' => $team->id,
        'user_id' => $captain->id,
        'joined_at' => now()->subDay(),
    ]);

    $result = (new LeaveTeam)->execute($team, $captain);

    expect($result)->toBeTrue();
    expect(CompetitionTeam::find($team->id))->toBeNull();
});

it('rotates the captaincy to the oldest active member when the captain leaves', function () {
    $captain = User::factory()->create();
    $oldest = User::factory()->create();
    $newer = User::factory()->create();
    $competition = Competition::factory()->create();
    $team = CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
        'captain_user_id' => $captain->id,
    ]);
    CompetitionTeamMember::factory()->create([
        'team_id' => $team->id,
        'user_id' => $captain->id,
        'joined_at' => now()->subDays(20),
    ]);
    CompetitionTeamMember::factory()->create([
        'team_id' => $team->id,
        'user_id' => $oldest->id,
        'joined_at' => now()->subDays(10),
    ]);
    CompetitionTeamMember::factory()->create([
        'team_id' => $team->id,
        'user_id' => $newer->id,
        'joined_at' => now()->subDays(2),
    ]);

    $result = (new LeaveTeam)->execute($team, $captain);

    expect($result)->toBeFalse();
    expect($team->fresh()->captain_user_id)->toBe($oldest->id);
});
