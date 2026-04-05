<?php

use App\Domain\Games\Models\Game;
use App\Domain\Orchestration\Actions\ProcessOrchestrationJob;
use App\Domain\Orchestration\Actions\ResolveMatchHandler;
use App\Domain\Orchestration\Contracts\MatchHandlerContract;
use App\Domain\Orchestration\Enums\GameServerStatus;
use App\Domain\Orchestration\Enums\OrchestrationJobStatus;
use App\Domain\Orchestration\Models\GameServer;
use App\Domain\Orchestration\Models\OrchestrationJob;

it('processes a job and sets server to in use', function () {
    $game = Game::factory()->create();
    $server = GameServer::factory()->available()->competition()->create(['game_id' => $game->id]);
    $job = OrchestrationJob::factory()->create([
        'game_id' => $game->id,
        'status' => OrchestrationJobStatus::Pending,
    ]);

    $mockHandler = Mockery::mock(MatchHandlerContract::class);
    $mockHandler->shouldReceive('supports')->andReturn(true);
    $mockHandler->shouldReceive('healthCheck')->andReturn(true);
    $mockHandler->shouldReceive('deploy')->once();

    app()->tag([$mockHandler::class], 'match_handlers');
    app()->instance($mockHandler::class, $mockHandler);

    app()->when(ResolveMatchHandler::class)
        ->needs('$handlers')
        ->give(fn () => [$mockHandler]);

    $action = app(ProcessOrchestrationJob::class);
    $action->execute($job);

    $job->refresh();
    $server->refresh();

    expect($job->status)->toBe(OrchestrationJobStatus::Active);
    expect($job->game_server_id)->toBe($server->id);
    expect($server->status)->toBe(GameServerStatus::InUse);
});

it('fails when no server is available', function () {
    $game = Game::factory()->create();
    $job = OrchestrationJob::factory()->create([
        'game_id' => $game->id,
        'status' => OrchestrationJobStatus::Pending,
    ]);

    $action = app(ProcessOrchestrationJob::class);
    $action->execute($job);

    $job->refresh();

    expect($job->status)->toBe(OrchestrationJobStatus::Failed);
    expect($job->error_message)->toContain('No available game server');
    expect($job->attempts)->toBe(1);
});

it('fails and releases server when health check fails', function () {
    $game = Game::factory()->create();
    $server = GameServer::factory()->available()->competition()->create(['game_id' => $game->id]);
    $job = OrchestrationJob::factory()->create([
        'game_id' => $game->id,
        'status' => OrchestrationJobStatus::Pending,
    ]);

    $mockHandler = Mockery::mock(MatchHandlerContract::class);
    $mockHandler->shouldReceive('supports')->andReturn(true);
    $mockHandler->shouldReceive('healthCheck')->andReturn(false);

    app()->when(ResolveMatchHandler::class)
        ->needs('$handlers')
        ->give(fn () => [$mockHandler]);

    $action = app(ProcessOrchestrationJob::class);
    $action->execute($job);

    $job->refresh();
    $server->refresh();

    expect($job->status)->toBe(OrchestrationJobStatus::Failed);
    expect($job->error_message)->toContain('health check failed');
    expect($server->status)->toBe(GameServerStatus::Available);
});
