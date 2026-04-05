<?php

use App\Domain\Competition\Actions\HandleLanBracketsWebhook;
use App\Domain\Competition\Events\MatchReadyForOrchestration;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Services\LanBracketsClient;
use App\Domain\Games\Models\Game;
use App\Domain\Orchestration\Models\OrchestrationJob;
use Illuminate\Support\Facades\Event;

it('dispatches MatchReadyForOrchestration for matches with all participants set', function () {
    Event::fake([MatchReadyForOrchestration::class]);

    $game = Game::factory()->create();
    $competition = Competition::factory()->create([
        'game_id' => $game->id,
        'lanbrackets_id' => 42,
    ]);

    $mockClient = Mockery::mock(LanBracketsClient::class);
    $mockClient->shouldReceive('getMatches')
        ->with(42, 1)
        ->andReturn([
            [
                'id' => 101,
                'status' => 'pending',
                'participants' => [
                    ['competition_participant_id' => 1, 'slot' => 1],
                    ['competition_participant_id' => 2, 'slot' => 2],
                ],
            ],
            [
                'id' => 102,
                'status' => 'pending',
                'participants' => [
                    ['competition_participant_id' => null, 'slot' => 1],
                    ['competition_participant_id' => null, 'slot' => 2],
                ],
            ],
        ]);

    $action = new HandleLanBracketsWebhook($mockClient);
    $action->execute('bracket.generated', [
        'data' => [
            'external_reference_id' => (string) $competition->id,
            'stage_id' => 1,
            'match_count' => 2,
        ],
    ]);

    Event::assertDispatched(MatchReadyForOrchestration::class, function ($event) use ($competition) {
        return $event->competition->id === $competition->id
            && $event->lanbracketsMatchId === 101;
    });

    Event::assertDispatchedTimes(MatchReadyForOrchestration::class, 1);
});

it('skips matches that are not in pending status', function () {
    Event::fake([MatchReadyForOrchestration::class]);

    $game = Game::factory()->create();
    $competition = Competition::factory()->create([
        'game_id' => $game->id,
        'lanbrackets_id' => 42,
    ]);

    $mockClient = Mockery::mock(LanBracketsClient::class);
    $mockClient->shouldReceive('getMatches')
        ->andReturn([
            [
                'id' => 101,
                'status' => 'finished',
                'participants' => [
                    ['competition_participant_id' => 1, 'slot' => 1],
                    ['competition_participant_id' => 2, 'slot' => 2],
                ],
            ],
        ]);

    $action = new HandleLanBracketsWebhook($mockClient);
    $action->execute('bracket.generated', [
        'data' => [
            'external_reference_id' => (string) $competition->id,
            'stage_id' => 1,
        ],
    ]);

    Event::assertNotDispatched(MatchReadyForOrchestration::class);
});

it('does not create duplicate orchestration jobs', function () {
    Event::fake([MatchReadyForOrchestration::class]);

    $game = Game::factory()->create();
    $competition = Competition::factory()->create([
        'game_id' => $game->id,
        'lanbrackets_id' => 42,
    ]);

    OrchestrationJob::factory()->create([
        'competition_id' => $competition->id,
        'lanbrackets_match_id' => 101,
        'game_id' => $game->id,
    ]);

    $mockClient = Mockery::mock(LanBracketsClient::class);
    $mockClient->shouldReceive('getMatches')
        ->andReturn([
            [
                'id' => 101,
                'status' => 'pending',
                'participants' => [
                    ['competition_participant_id' => 1, 'slot' => 1],
                    ['competition_participant_id' => 2, 'slot' => 2],
                ],
            ],
        ]);

    $action = new HandleLanBracketsWebhook($mockClient);
    $action->execute('bracket.generated', [
        'data' => [
            'external_reference_id' => (string) $competition->id,
            'stage_id' => 1,
        ],
    ]);

    Event::assertNotDispatched(MatchReadyForOrchestration::class);
});
