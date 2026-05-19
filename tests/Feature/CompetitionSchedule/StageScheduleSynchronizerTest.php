<?php

use App\Domain\Api\Clients\LanBracketsClient;
use App\Domain\Competition\Models\Competition;
use App\Domain\CompetitionSchedule\Models\CompetitionRoundSchedule;
use App\Domain\CompetitionSchedule\Models\CompetitionStageSchedule;
use App\Domain\CompetitionSchedule\Services\DurationEstimator;
use App\Domain\CompetitionSchedule\Services\RoundDurationDefaults;
use App\Domain\CompetitionSchedule\Services\StageScheduleSynchronizer;
use App\Domain\Games\Models\Game;
use Illuminate\Support\Str;

beforeEach(function (): void {
    config()->set('lanbrackets.enabled', true);
    $this->game = Game::factory()->create(['avg_match_minutes' => 25]);
    $this->competition = Competition::factory()->create([
        'game_id' => $this->game->id,
        'lanbrackets_id' => (string) Str::ulid(),
        'max_teams' => 8,
    ]);
});

/**
 * @param  array<int, array<string, mixed>>  $stagesPayload
 * @param  array<string, array<int, array<string, mixed>>>  $matchesByStageId  keyed by lanbrackets_stage_id (string)
 */
function makeSynchronizer(array $stagesPayload, array $matchesByStageId = []): StageScheduleSynchronizer
{
    $client = Mockery::mock(LanBracketsClient::class);
    $client->shouldReceive('getStages')->andReturn($stagesPayload);
    $client->shouldReceive('getMatches')
        ->andReturnUsing(fn (string $competitionId, string $stageId) => $matchesByStageId[$stageId] ?? []);

    return new StageScheduleSynchronizer($client, new DurationEstimator, new RoundDurationDefaults);
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

it('derives round schedules from match round_numbers on first sync (RND-001)', function (): void {
    $sync = makeSynchronizer(
        stagesPayload: [
            ['id' => 11, 'name' => 'Playoffs', 'stage_type' => 'single_elimination', 'order' => 1, 'match_count' => 15],
        ],
        matchesByStageId: [
            '11' => [
                ['round_number' => 1], ['round_number' => 1], ['round_number' => 1], ['round_number' => 1],
                ['round_number' => 1], ['round_number' => 1], ['round_number' => 1], ['round_number' => 1],
                ['round_number' => 2], ['round_number' => 2], ['round_number' => 2], ['round_number' => 2],
                ['round_number' => 3], ['round_number' => 3],
                ['round_number' => 4],
            ],
        ],
    );

    $sync->syncCompetition($this->competition);

    $stage = CompetitionStageSchedule::query()->where('lanbrackets_stage_id', '11')->firstOrFail();
    $rounds = CompetitionRoundSchedule::query()->where('stage_schedule_id', $stage->id)->orderBy('sequence')->get();

    expect($rounds)->toHaveCount(4)
        ->and($rounds->pluck('lanbrackets_round_number')->all())->toBe([1, 2, 3, 4])
        ->and($rounds->pluck('label')->all())->toBe(['First Matches', 'Quarterfinal', 'Semifinal', 'Final']);
});

it('preserves round overrides on re-sync (RND-002)', function (): void {
    $sync = makeSynchronizer(
        stagesPayload: [
            ['id' => 11, 'name' => 'Playoffs', 'stage_type' => 'single_elimination', 'order' => 1, 'match_count' => 3],
        ],
        matchesByStageId: [
            '11' => [['round_number' => 1], ['round_number' => 2], ['round_number' => 3]],
        ],
    );

    $sync->syncCompetition($this->competition);
    $round = CompetitionRoundSchedule::query()->where('lanbrackets_round_number', 2)->firstOrFail();
    $round->update([
        'estimated_duration_minutes' => 777,
        'duration_overridden' => true,
        'label' => 'Custom Round',
    ]);

    $sync->syncCompetition($this->competition->fresh());

    $round->refresh();
    expect($round->estimated_duration_minutes)->toBe(777)
        ->and($round->duration_overridden)->toBeTrue()
        ->and($round->label)->toBe('Custom Round');
});

it('deletes orphan rounds whose round_number disappears from LanBrackets (RND-003)', function (): void {
    $sync = makeSynchronizer(
        stagesPayload: [
            ['id' => 11, 'name' => 'Playoffs', 'stage_type' => 'single_elimination', 'order' => 1],
        ],
        matchesByStageId: [
            '11' => [['round_number' => 1], ['round_number' => 2]],
        ],
    );
    $sync->syncCompetition($this->competition);
    expect(CompetitionRoundSchedule::query()->count())->toBe(2);

    $sync2 = makeSynchronizer(
        stagesPayload: [
            ['id' => 11, 'name' => 'Playoffs', 'stage_type' => 'single_elimination', 'order' => 1],
        ],
        matchesByStageId: [
            '11' => [['round_number' => 1]],
        ],
    );
    $sync2->syncCompetition($this->competition->fresh());

    expect(CompetitionRoundSchedule::query()->pluck('lanbrackets_round_number')->all())->toBe([1]);
});
