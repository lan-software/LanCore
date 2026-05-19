<?php

use App\Domain\Api\Clients\LanBracketsClient;
use App\Domain\Competition\Exceptions\LanBracketsDisabledException;
use App\Domain\Competition\Exceptions\LanBracketsRequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    config(['lanbrackets.enabled' => true]);
    config(['lanbrackets.internal_url' => 'http://lanbrackets.test']);
    config(['lanbrackets.token' => 'lbt_test_token']);
});

it('throws LanBracketsDisabledException when disabled', function () {
    config(['lanbrackets.enabled' => false]);

    $client = new LanBracketsClient;

    $client->createCompetition(['name' => 'Test', 'type' => 'tournament', 'stage_type' => 'single_elimination']);
})->throws(LanBracketsDisabledException::class);

it('creates a competition via API', function () {
    $createdId = (string) Str::ulid();
    Http::fake([
        'lanbrackets.test/api/v1/competitions' => Http::response([
            'data' => ['id' => $createdId, 'name' => 'Test Competition'],
        ], 201),
    ]);

    $client = new LanBracketsClient;
    $result = $client->createCompetition([
        'name' => 'Test Competition',
        'type' => 'tournament',
        'stage_type' => 'single_elimination',
        'external_reference_id' => (string) Str::ulid(),
        'source_system' => 'lancore',
    ]);

    expect($result['id'])->toBe($createdId);
    expect($result['name'])->toBe('Test Competition');

    Http::assertSent(function ($request) {
        return $request->url() === 'http://lanbrackets.test/api/v1/competitions'
            && $request->hasHeader('Authorization', 'Bearer lbt_test_token')
            && $request['name'] === 'Test Competition';
    });
});

it('throws LanBracketsRequestException on API error', function () {
    Http::fake([
        'lanbrackets.test/api/v1/competitions' => Http::response([
            'message' => 'Validation failed',
        ], 422),
    ]);

    $client = new LanBracketsClient;
    $client->createCompetition(['name' => 'Test', 'type' => 'tournament', 'stage_type' => 'single_elimination']);
})->throws(LanBracketsRequestException::class, 'Validation failed');

it('regenerates a share token', function () {
    $competitionId = (string) Str::ulid();
    Http::fake([
        "lanbrackets.test/api/v1/competitions/{$competitionId}/share-token" => Http::response([
            'share_token' => 'abc123token',
        ]),
    ]);

    $client = new LanBracketsClient;
    $token = $client->regenerateShareToken($competitionId);

    expect($token)->toBe('abc123token');
});

it('reports a match result', function () {
    $competitionId = (string) Str::ulid();
    $matchId = (string) Str::ulid();
    Http::fake([
        "lanbrackets.test/api/v1/competitions/{$competitionId}/matches/{$matchId}/result" => Http::response([
            'data' => ['id' => $matchId, 'status' => 'finished'],
        ]),
    ]);

    $client = new LanBracketsClient;
    $result = $client->reportMatchResult($competitionId, $matchId, [
        ['participant_id' => (string) Str::ulid(), 'score' => 16],
        ['participant_id' => (string) Str::ulid(), 'score' => 10],
    ]);

    expect($result['status'])->toBe('finished');
});
