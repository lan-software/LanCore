<?php

use App\Domain\Games\Models\Game;
use App\Domain\Games\Models\GameMode;
use App\Domain\Orchestration\Actions\SelectServerForMatch;
use App\Domain\Orchestration\Models\GameServer;

it('selects an available server for a game', function () {
    $game = Game::factory()->create();
    $server = GameServer::factory()->available()->create(['game_id' => $game->id]);

    $action = app(SelectServerForMatch::class);
    $result = $action->execute($game->id);

    expect($result)->not->toBeNull();
    expect($result->id)->toBe($server->id);
});

it('returns null when no server is available', function () {
    $game = Game::factory()->create();
    GameServer::factory()->inUse()->create(['game_id' => $game->id]);

    $action = app(SelectServerForMatch::class);
    $result = $action->execute($game->id);

    expect($result)->toBeNull();
});

it('does not select servers for different games', function () {
    $game1 = Game::factory()->create();
    $game2 = Game::factory()->create();
    GameServer::factory()->available()->create(['game_id' => $game2->id]);

    $action = app(SelectServerForMatch::class);
    $result = $action->execute($game1->id);

    expect($result)->toBeNull();
});

it('prioritizes competition servers over flexible and casual', function () {
    $game = Game::factory()->create();

    $casual = GameServer::factory()->available()->casual()->create(['game_id' => $game->id, 'name' => 'Casual']);
    $flexible = GameServer::factory()->available()->flexible()->create(['game_id' => $game->id, 'name' => 'Flexible']);
    $competition = GameServer::factory()->available()->competition()->create(['game_id' => $game->id, 'name' => 'Competition']);

    $action = app(SelectServerForMatch::class);
    $result = $action->execute($game->id);

    expect($result->id)->toBe($competition->id);
});

it('falls back to flexible when no competition server available', function () {
    $game = Game::factory()->create();

    $casual = GameServer::factory()->available()->casual()->create(['game_id' => $game->id]);
    $flexible = GameServer::factory()->available()->flexible()->create(['game_id' => $game->id]);

    $action = app(SelectServerForMatch::class);
    $result = $action->execute($game->id);

    expect($result->id)->toBe($flexible->id);
});

it('prefers servers with matching game mode', function () {
    $game = Game::factory()->create();
    $mode = GameMode::factory()->create(['game_id' => $game->id]);

    $generic = GameServer::factory()->available()->competition()->create([
        'game_id' => $game->id,
        'game_mode_id' => null,
        'name' => 'Generic',
    ]);
    $specific = GameServer::factory()->available()->competition()->create([
        'game_id' => $game->id,
        'game_mode_id' => $mode->id,
        'name' => 'Specific',
    ]);

    $action = app(SelectServerForMatch::class);
    $result = $action->execute($game->id, $mode->id);

    expect($result->id)->toBe($specific->id);
});
