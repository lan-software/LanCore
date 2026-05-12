<?php

use App\Domain\Competition\Actions\UpdateCompetition;
use App\Domain\Competition\Enums\CompetitionStatus;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\CompetitionTeam;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

/**
 * @see docs/mil-std-498/SRS.md COMP-F-003
 */
beforeEach(function (): void {
    Queue::fake();
    Bus::fake();
});

it('allows Draft → Published', function (): void {
    $competition = Competition::factory()->create(['status' => CompetitionStatus::Draft]);

    app(UpdateCompetition::class)->execute($competition, ['status' => 'published']);

    expect($competition->fresh()->status)->toBe(CompetitionStatus::Published);
});

it('rejects Draft → RegistrationOpen (must go via Published)', function (): void {
    $competition = Competition::factory()->create(['status' => CompetitionStatus::Draft]);

    expect(fn () => app(UpdateCompetition::class)->execute($competition, ['status' => 'registration_open']))
        ->toThrow(ValidationException::class);
});

it('allows Published → RegistrationOpen', function (): void {
    $competition = Competition::factory()->create(['status' => CompetitionStatus::Published]);

    app(UpdateCompetition::class)->execute($competition, ['status' => 'registration_open']);

    expect($competition->fresh()->status)->toBe(CompetitionStatus::RegistrationOpen);
});

it('allows Published → Draft (revert)', function (): void {
    $competition = Competition::factory()->create(['status' => CompetitionStatus::Published]);

    app(UpdateCompetition::class)->execute($competition, ['status' => 'draft']);

    expect($competition->fresh()->status)->toBe(CompetitionStatus::Draft);
});

it('allows RegistrationClosed → RegistrationOpen and preserves teams', function (): void {
    $competition = Competition::factory()->create(['status' => CompetitionStatus::RegistrationClosed]);
    $team = CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
    ]);

    app(UpdateCompetition::class)->execute($competition, ['status' => 'registration_open']);

    expect($competition->fresh()->status)->toBe(CompetitionStatus::RegistrationOpen);
    expect($competition->fresh()->teams()->whereKey($team->id)->exists())->toBeTrue();
});

it('rejects RegistrationOpen → Draft', function (): void {
    $competition = Competition::factory()->create(['status' => CompetitionStatus::RegistrationOpen]);

    expect(fn () => app(UpdateCompetition::class)->execute($competition, ['status' => 'draft']))
        ->toThrow(ValidationException::class);
});

it('Published is publicly visible; Draft is not', function (): void {
    expect(CompetitionStatus::Published->isPubliclyVisible())->toBeTrue();
    expect(CompetitionStatus::RegistrationOpen->isPubliclyVisible())->toBeTrue();
    expect(CompetitionStatus::Draft->isPubliclyVisible())->toBeFalse();
    expect(CompetitionStatus::RegistrationClosed->isPubliclyVisible())->toBeFalse();
});

it('Published does not allow team registration (isRegistrationOpen stays false)', function (): void {
    $competition = Competition::factory()->create(['status' => CompetitionStatus::Published]);

    expect($competition->isRegistrationOpen())->toBeFalse();
});
