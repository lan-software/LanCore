<?php

use App\Domain\Api\Clients\LanBracketsClient;
use App\Domain\Competition\Models\Competition;
use App\Domain\CompetitionSchedule\Models\CompetitionStageSchedule;
use App\Domain\CompetitionSchedule\Services\DurationEstimator;
use App\Domain\CompetitionSchedule\Services\StageScheduleSynchronizer;
use App\Domain\Games\Models\Game;

beforeEach(function (): void {
    config()->set('lanbrackets.enabled', true);
    $this->game = Game::factory()->create(['avg_match_minutes' => 25]);
    $this->competition = Competition::factory()->create([
        'game_id' => $this->game->id,
        'lanbrackets_id' => 7,
        'max_teams' => 8,
    ]);
});

function makeSynchronizer(array $stagesPayload): StageScheduleSynchronizer
{
    $client = Mockery::mock(LanBracketsClient::class);
    $client->shouldReceive('getStages')->andReturn($stagesPayload);

    return new StageScheduleSynchronizer($client, new DurationEstimator);
}

it('upserts a new stage schedule on first sync', function (): void {
    $sync = makeSynchronizer([
        ['id' => 11, 'name' => 'Groups', 'stage_type' => 'group_stage', 'order' => 1, 'match_count' => 6],
        ['id' => 12, 'name' => 'Playoffs', 'stage_type' => 'single_elimination', 'order' => 2, 'match_count' => 3],
    ]);

    $sync->syncCompetition($this->competition);

    expect(CompetitionStageSchedule::query()->where('competition_id', $this->competition->id)->count())->toBe(2);
});

it('preserves organizer overrides on duration during re-sync', function (): void {
    $sync = makeSynchronizer([
        ['id' => 11, 'name' => 'Groups', 'stage_type' => 'group_stage', 'order' => 1, 'match_count' => 6],
    ]);

    $sync->syncCompetition($this->competition);

    $schedule = CompetitionStageSchedule::query()->where('lanbrackets_stage_id', '11')->firstOrFail();
    $schedule->update([
        'estimated_duration_minutes' => 999,
        'duration_overridden' => true,
    ]);

    $sync->syncCompetition($this->competition->fresh());

    $schedule->refresh();
    expect($schedule->estimated_duration_minutes)->toBe(999)
        ->and($schedule->duration_overridden)->toBeTrue();
});

it('deletes orphan schedules whose stages disappear from LanBrackets', function (): void {
    $orphan = CompetitionStageSchedule::factory()->for($this->competition)->create([
        'lanbrackets_stage_id' => '999',
    ]);

    $sync = makeSynchronizer([
        ['id' => 11, 'name' => 'Groups', 'stage_type' => 'group_stage'],
    ]);

    $sync->syncCompetition($this->competition);

    expect(CompetitionStageSchedule::query()->whereKey($orphan->id)->exists())->toBeFalse();
});

it('skips sync for competitions not linked to LanBrackets', function (): void {
    $orphan = Competition::factory()->create(['lanbrackets_id' => null]);

    $touched = makeSynchronizer([])->syncCompetition($orphan);

    expect($touched)->toBe(0);
});
