<?php

use App\Domain\Api\Clients\LanBracketsClient;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\CompetitionTeam;
use App\Domain\Competition\Models\CompetitionTeamMember;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function (): void {
    config()->set('lanbrackets.enabled', true);

    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
});

it('resolves team_id and team_name from the enriched payload via external_reference_id', function (): void {
    $user = User::factory()->withRole(RoleName::User)->create();
    $competition = Competition::factory()->syncedToLanBrackets()->create(['lanbrackets_id' => 700]);

    $teamA = CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
        'name' => 'Alpha',
        'lanbrackets_id' => 9001,
    ]);
    $teamB = CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
        'name' => 'Bravo',
        'lanbrackets_id' => 9002,
    ]);
    CompetitionTeamMember::factory()->create(['team_id' => $teamA->id, 'user_id' => $user->id]);

    $mock = Mockery::mock(LanBracketsClient::class);
    $mock->shouldReceive('getStages')
        ->with(700)
        ->andReturn([
            ['id' => 1, 'name' => 'Main', 'stage_type' => 'single_elimination', 'status' => 'running'],
        ]);
    $mock->shouldReceive('getMatches')
        ->with(700, 1)
        ->andReturn([
            [
                'id' => 5050,
                'round_number' => 1,
                'sequence' => 1,
                'status' => 'pending',
                'match_participants' => [
                    [
                        'competition_participant_id' => 111,
                        'participant_type' => 'team',
                        'participant_id' => 9001,
                        'participant_name' => 'Alpha (from LB)',
                        'external_reference_id' => (string) $teamA->id,
                        'source_system' => 'lancore',
                        'score' => null,
                        'result' => null,
                    ],
                    [
                        'competition_participant_id' => 112,
                        'participant_type' => 'team',
                        'participant_id' => 9002,
                        'participant_name' => 'Bravo (from LB)',
                        'external_reference_id' => (string) $teamB->id,
                        'source_system' => 'lancore',
                        'score' => null,
                        'result' => null,
                    ],
                ],
            ],
        ]);

    $this->app->instance(LanBracketsClient::class, $mock);

    $this->actingAs($user)
        ->get("/portal/competitions/{$competition->id}/matches")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('competitions/user/Matches')
                ->where('stages.0.matches.0.participants.0.team_id', $teamA->id)
                ->where('stages.0.matches.0.participants.0.team_name', 'Alpha')
                ->where('stages.0.matches.0.participants.1.team_id', $teamB->id)
                ->where('stages.0.matches.0.participants.1.team_name', 'Bravo')
                ->where('stages.0.matches.0.user_is_participant', true)
        );
});

it('falls back to participant_name when the enriched fields are absent (legacy payload)', function (): void {
    $user = User::factory()->withRole(RoleName::User)->create();
    $competition = Competition::factory()->syncedToLanBrackets()->create(['lanbrackets_id' => 701]);

    $team = CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
        'name' => 'Solo',
        'lanbrackets_id' => null,
    ]);
    CompetitionTeamMember::factory()->create(['team_id' => $team->id, 'user_id' => $user->id]);

    $mock = Mockery::mock(LanBracketsClient::class);
    $mock->shouldReceive('getStages')
        ->with(701)
        ->andReturn([
            ['id' => 1, 'name' => 'Main', 'status' => 'running'],
        ]);
    $mock->shouldReceive('getMatches')
        ->with(701, 1)
        ->andReturn([
            [
                'id' => 6060,
                'round_number' => 1,
                'sequence' => 1,
                'status' => 'pending',
                'match_participants' => [
                    [
                        'competition_participant_id' => 211,
                        'score' => null,
                        'result' => null,
                    ],
                    [
                        'competition_participant_id' => 212,
                        'participant_name' => 'Legacy Display Name',
                        'score' => null,
                        'result' => null,
                    ],
                ],
            ],
        ]);

    $this->app->instance(LanBracketsClient::class, $mock);

    $this->actingAs($user)
        ->get("/portal/competitions/{$competition->id}/matches")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('competitions/user/Matches')
                ->where('stages.0.matches.0.participants.0.team_id', null)
                ->where('stages.0.matches.0.participants.0.team_name', null)
                ->where('stages.0.matches.0.participants.1.team_id', null)
                ->where('stages.0.matches.0.participants.1.team_name', 'Legacy Display Name')
                ->where('stages.0.matches.0.user_is_participant', false)
        );
});

it('marks user_is_participant true when the user is a member of a resolved team', function (): void {
    $user = User::factory()->withRole(RoleName::User)->create();
    $other = User::factory()->withRole(RoleName::User)->create();

    $competition = Competition::factory()->syncedToLanBrackets()->create(['lanbrackets_id' => 702]);

    $myTeam = CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
        'name' => 'Mine',
        'lanbrackets_id' => 8001,
    ]);
    $otherTeam = CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
        'name' => 'Theirs',
        'lanbrackets_id' => 8002,
    ]);
    CompetitionTeamMember::factory()->create(['team_id' => $myTeam->id, 'user_id' => $user->id]);
    CompetitionTeamMember::factory()->create(['team_id' => $otherTeam->id, 'user_id' => $other->id]);

    $mock = Mockery::mock(LanBracketsClient::class);
    $mock->shouldReceive('getStages')
        ->with(702)
        ->andReturn([
            ['id' => 1, 'name' => 'Main', 'status' => 'running'],
        ]);
    $mock->shouldReceive('getMatches')
        ->with(702, 1)
        ->andReturn([
            [
                'id' => 7070,
                'status' => 'pending',
                'match_participants' => [
                    [
                        'competition_participant_id' => 311,
                        'participant_type' => 'team',
                        'external_reference_id' => (string) $myTeam->id,
                        'participant_name' => 'Mine',
                    ],
                    [
                        'competition_participant_id' => 312,
                        'participant_type' => 'team',
                        'external_reference_id' => (string) $otherTeam->id,
                        'participant_name' => 'Theirs',
                    ],
                ],
            ],
        ]);

    $this->app->instance(LanBracketsClient::class, $mock);

    $this->actingAs($user)
        ->get("/portal/competitions/{$competition->id}/matches")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->where('stages.0.matches.0.user_is_participant', true)
                ->where('userTeam.id', $myTeam->id)
        );
});
