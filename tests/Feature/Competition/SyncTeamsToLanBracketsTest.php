<?php

use App\Domain\Api\Clients\LanBracketsClient;
use App\Domain\Competition\Actions\UpdateCompetition;
use App\Domain\Competition\Enums\CompetitionStatus;
use App\Domain\Competition\Exceptions\LanBracketsRequestException;
use App\Domain\Competition\Jobs\GenerateLanBracketsStages;
use App\Domain\Competition\Jobs\SyncCompetitionToLanBrackets;
use App\Domain\Competition\Jobs\SyncTeamsToLanBrackets;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\CompetitionTeam;
use Illuminate\Support\Facades\Bus;

beforeEach(function (): void {
    config()->set('lanbrackets.enabled', true);
});

it('upserts each team and persists the returned lanbrackets_id before bulk add', function (): void {
    $competition = Competition::factory()->registrationOpen()->syncedToLanBrackets()->create([
        'lanbrackets_id' => 100,
    ]);

    $teamA = CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
        'name' => 'Alpha',
        'tag' => 'ALP',
        'lanbrackets_id' => null,
    ]);
    $teamB = CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
        'name' => 'Bravo',
        'tag' => 'BRV',
        'lanbrackets_id' => null,
    ]);

    $mock = Mockery::mock(LanBracketsClient::class);

    $mock->shouldReceive('upsertTeam')
        ->once()
        ->withArgs(function (array $payload) use ($teamA): bool {
            return $payload['name'] === 'Alpha'
                && $payload['external_reference_id'] === (string) $teamA->id
                && $payload['source_system'] === 'lancore';
        })
        ->andReturn(['id' => 501, 'name' => 'Alpha']);

    $mock->shouldReceive('upsertTeam')
        ->once()
        ->withArgs(function (array $payload) use ($teamB): bool {
            return $payload['name'] === 'Bravo'
                && $payload['external_reference_id'] === (string) $teamB->id;
        })
        ->andReturn(['id' => 502, 'name' => 'Bravo']);

    $mock->shouldReceive('bulkAddParticipants')
        ->once()
        ->withArgs(function (int $compId, array $participants): bool {
            $ids = collect($participants)->pluck('participant_id')->all();

            return $compId === 100
                && in_array(501, $ids, true)
                && in_array(502, $ids, true);
        })
        ->andReturn([]);

    $this->app->instance(LanBracketsClient::class, $mock);

    (new SyncTeamsToLanBrackets($competition))->handle($mock);

    expect($teamA->fresh()->lanbrackets_id)->toBe(501);
    expect($teamB->fresh()->lanbrackets_id)->toBe(502);
});

it('skips teams whose upsert returned 4xx and have no prior lanbrackets_id', function (): void {
    $competition = Competition::factory()->registrationOpen()->syncedToLanBrackets()->create([
        'lanbrackets_id' => 200,
    ]);

    $badTeam = CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
        'name' => 'Doomed',
        'lanbrackets_id' => null,
    ]);
    $goodTeam = CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
        'name' => 'Survives',
        'lanbrackets_id' => null,
    ]);

    $mock = Mockery::mock(LanBracketsClient::class);

    $mock->shouldReceive('upsertTeam')
        ->once()
        ->withArgs(fn (array $p): bool => $p['name'] === 'Doomed')
        ->andThrow(new LanBracketsRequestException('Validation failed', 422));

    $mock->shouldReceive('upsertTeam')
        ->once()
        ->withArgs(fn (array $p): bool => $p['name'] === 'Survives')
        ->andReturn(['id' => 777]);

    $mock->shouldReceive('bulkAddParticipants')
        ->once()
        ->withArgs(function (int $compId, array $participants): bool {
            $ids = collect($participants)->pluck('participant_id')->all();

            return $compId === 200
                && $ids === [777];
        })
        ->andReturn([]);

    $this->app->instance(LanBracketsClient::class, $mock);

    (new SyncTeamsToLanBrackets($competition))->handle($mock);

    expect($badTeam->fresh()->lanbrackets_id)->toBeNull();
    expect($goodTeam->fresh()->lanbrackets_id)->toBe(777);
});

it('re-raises 5xx upsert failures so the queue retries', function (): void {
    $competition = Competition::factory()->registrationOpen()->syncedToLanBrackets()->create([
        'lanbrackets_id' => 300,
    ]);

    CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
        'name' => 'BoomTeam',
    ]);

    $mock = Mockery::mock(LanBracketsClient::class);
    $mock->shouldReceive('upsertTeam')
        ->once()
        ->andThrow(new LanBracketsRequestException('LanBrackets exploded', 502));
    $mock->shouldNotReceive('bulkAddParticipants');

    $this->app->instance(LanBracketsClient::class, $mock);

    expect(fn () => (new SyncTeamsToLanBrackets($competition))->handle($mock))
        ->toThrow(LanBracketsRequestException::class);
});

it('treats bulkAddParticipants 4xx as idempotent success', function (): void {
    $competition = Competition::factory()->registrationOpen()->syncedToLanBrackets()->create([
        'lanbrackets_id' => 400,
    ]);

    CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
        'name' => 'Re-Sync',
    ]);

    $mock = Mockery::mock(LanBracketsClient::class);
    $mock->shouldReceive('upsertTeam')
        ->once()
        ->andReturn(['id' => 9001]);
    $mock->shouldReceive('bulkAddParticipants')
        ->once()
        ->andThrow(new LanBracketsRequestException('already registered', 422));

    $this->app->instance(LanBracketsClient::class, $mock);

    // Should not throw — handler swallows 4xx so the chained generator runs.
    (new SyncTeamsToLanBrackets($competition))->handle($mock);

    expect(true)->toBeTrue();
});

it('does nothing when the competition has not yet been synced to LanBrackets', function (): void {
    $competition = Competition::factory()->registrationOpen()->create([
        'lanbrackets_id' => null,
    ]);
    CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
    ]);

    $mock = Mockery::mock(LanBracketsClient::class);
    $mock->shouldNotReceive('upsertTeam');
    $mock->shouldNotReceive('bulkAddParticipants');

    $this->app->instance(LanBracketsClient::class, $mock);

    (new SyncTeamsToLanBrackets($competition))->handle($mock);

    expect(true)->toBeTrue();
});

it('chains SyncTeams + GenerateLanBracketsStages on RegistrationClosed transition', function (): void {
    Bus::fake();

    $competition = Competition::factory()->registrationOpen()->syncedToLanBrackets()->create([
        'lanbrackets_id' => 500,
    ]);

    CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
    ]);

    (new UpdateCompetition)->execute($competition, [
        'status' => CompetitionStatus::RegistrationClosed,
    ]);

    Bus::assertChained([
        SyncTeamsToLanBrackets::class,
        GenerateLanBracketsStages::class,
    ]);

    Bus::assertDispatched(SyncCompetitionToLanBrackets::class);
});
