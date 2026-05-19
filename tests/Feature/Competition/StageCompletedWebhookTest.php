<?php

use App\Domain\Api\Clients\LanBracketsClient;
use App\Domain\Competition\Actions\HandleLanBracketsWebhook;
use App\Domain\Competition\Exceptions\LanBracketsRequestException;
use App\Domain\Competition\Jobs\GenerateLanBracketsStages;
use App\Domain\Competition\Models\Competition;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

beforeEach(function (): void {
    config()->set('lanbrackets.enabled', true);
});

it('dispatches GenerateLanBracketsStages when a next pending stage exists', function (): void {
    Bus::fake();

    $competitionLbId = (string) Str::ulid();
    $competition = Competition::factory()->syncedToLanBrackets()->create([
        'lanbrackets_id' => $competitionLbId,
    ]);

    $mock = Mockery::mock(LanBracketsClient::class);
    $mock->shouldReceive('getStages')
        ->with($competitionLbId)
        ->andReturn([
            ['id' => 11, 'order' => 1, 'status' => 'finished'],
            ['id' => 12, 'order' => 2, 'status' => 'pending'],
            ['id' => 13, 'order' => 3, 'status' => 'pending'],
        ]);

    $action = new HandleLanBracketsWebhook($mock);
    $action->execute('stage.completed', [
        'data' => [
            'external_reference_id' => (string) $competition->id,
            'stage_id' => 11,
        ],
    ]);

    Bus::assertDispatched(GenerateLanBracketsStages::class, function ($job) use ($competition): bool {
        return $job->competition->id === $competition->id;
    });
});

it('is a no-op when the completed stage is the last stage', function (): void {
    Bus::fake();

    $competitionLbId = (string) Str::ulid();
    $competition = Competition::factory()->syncedToLanBrackets()->create([
        'lanbrackets_id' => $competitionLbId,
    ]);

    $mock = Mockery::mock(LanBracketsClient::class);
    $mock->shouldReceive('getStages')
        ->with($competitionLbId)
        ->andReturn([
            ['id' => 21, 'order' => 1, 'status' => 'finished'],
            ['id' => 22, 'order' => 2, 'status' => 'finished'],
        ]);

    $action = new HandleLanBracketsWebhook($mock);
    $action->execute('stage.completed', [
        'data' => [
            'external_reference_id' => (string) $competition->id,
            'stage_id' => 22,
        ],
    ]);

    Bus::assertNotDispatched(GenerateLanBracketsStages::class);
});

it('is a no-op when external_reference_id is missing', function (): void {
    Bus::fake();

    $mock = Mockery::mock(LanBracketsClient::class);
    $mock->shouldNotReceive('getStages');

    $action = new HandleLanBracketsWebhook($mock);
    $action->execute('stage.completed', [
        'data' => [
            'stage_id' => 99,
        ],
    ]);

    Bus::assertNotDispatched(GenerateLanBracketsStages::class);
});

it('is a no-op when getStages throws', function (): void {
    Bus::fake();

    $competitionLbId = (string) Str::ulid();
    $competition = Competition::factory()->syncedToLanBrackets()->create([
        'lanbrackets_id' => $competitionLbId,
    ]);

    $mock = Mockery::mock(LanBracketsClient::class);
    $mock->shouldReceive('getStages')
        ->with($competitionLbId)
        ->andThrow(new LanBracketsRequestException('LanBrackets unreachable', 500));

    $action = new HandleLanBracketsWebhook($mock);
    $action->execute('stage.completed', [
        'data' => [
            'external_reference_id' => (string) $competition->id,
            'stage_id' => 11,
        ],
    ]);

    Bus::assertNotDispatched(GenerateLanBracketsStages::class);
});

it('is a no-op when the completed stage_id is not found in the stage list', function (): void {
    Bus::fake();

    $competitionLbId = (string) Str::ulid();
    $competition = Competition::factory()->syncedToLanBrackets()->create([
        'lanbrackets_id' => $competitionLbId,
    ]);

    $mock = Mockery::mock(LanBracketsClient::class);
    $mock->shouldReceive('getStages')
        ->with($competitionLbId)
        ->andReturn([
            ['id' => 51, 'order' => 1, 'status' => 'finished'],
            ['id' => 52, 'order' => 2, 'status' => 'pending'],
        ]);

    $action = new HandleLanBracketsWebhook($mock);
    $action->execute('stage.completed', [
        'data' => [
            'external_reference_id' => (string) $competition->id,
            'stage_id' => 999,
        ],
    ]);

    Bus::assertNotDispatched(GenerateLanBracketsStages::class);
});
