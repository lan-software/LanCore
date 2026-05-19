<?php

use App\Domain\Competition\Models\Competition;
use App\Domain\CompetitionSchedule\Services\DurationEstimator;
use App\Domain\Games\Models\Game;
use App\Domain\Games\Models\GameMode;

beforeEach(function (): void {
    $this->game = Game::factory()->create(['avg_match_minutes' => 30, 'avg_stage_minutes' => 90]);
    $this->competition = Competition::factory()->create([
        'game_id' => $this->game->id,
        'max_teams' => 8,
    ]);
    $this->estimator = new DurationEstimator;
});

it('uses match_count × avg_match_minutes when LB reports match count', function (): void {
    $estimate = $this->estimator->estimate($this->competition, ['stage_type' => 'single_elimination', 'match_count' => 7]);

    expect($estimate->minutes)->toBe(7 * 30)
        ->and($estimate->strategy)->toBe('match_count');
});

it('falls back to single-elim formula when match count missing', function (): void {
    $estimate = $this->estimator->estimate($this->competition, ['stage_type' => 'single_elimination', 'team_count' => 8]);

    // 8 teams → 3 rounds × 30 minutes
    expect($estimate->minutes)->toBe(90);
});

it('estimates round-robin via combinations', function (): void {
    $estimate = $this->estimator->estimate($this->competition, ['stage_type' => 'round_robin', 'team_count' => 4]);

    // C(4,2) = 6 matches × 30 minutes
    expect($estimate->minutes)->toBe(180);
});

it('estimates swiss from total_rounds setting', function (): void {
    $estimate = $this->estimator->estimate(
        $this->competition,
        ['stage_type' => 'swiss', 'team_count' => 8, 'settings' => ['total_rounds' => 5]],
    );

    // 5 rounds × 4 matches per round × 30 minutes
    expect($estimate->minutes)->toBe(600);
});

it('uses Game.match_length_minutes over Game.avg_match_minutes', function (): void {
    $game = Game::factory()->create([
        'avg_match_minutes' => 30,
        'avg_stage_minutes' => 90,
        'match_length_minutes' => 45,
    ]);
    $competition = Competition::factory()->create(['game_id' => $game->id, 'max_teams' => 8]);

    $estimate = $this->estimator->estimate($competition, [
        'stage_type' => 'single_elimination',
        'match_count' => 7,
    ]);

    expect($estimate->minutes)->toBe(7 * 45);
});

it('uses GameMode.match_length_minutes over Game.match_length_minutes', function (): void {
    $game = Game::factory()->create([
        'avg_match_minutes' => 30,
        'avg_stage_minutes' => 90,
        'match_length_minutes' => 45,
    ]);
    $mode = GameMode::factory()->create([
        'game_id' => $game->id,
        'match_length_minutes' => 60,
    ]);
    $competition = Competition::factory()->create([
        'game_id' => $game->id,
        'game_mode_id' => $mode->id,
        'max_teams' => 8,
    ]);

    $estimate = $this->estimator->estimate($competition, [
        'stage_type' => 'single_elimination',
        'match_count' => 7,
    ]);

    expect($estimate->minutes)->toBe(7 * 60);
});

it('uses Competition.match_length_minutes override over inherited values', function (): void {
    $game = Game::factory()->create([
        'avg_match_minutes' => 30,
        'avg_stage_minutes' => 90,
        'match_length_minutes' => 45,
    ]);
    $mode = GameMode::factory()->create([
        'game_id' => $game->id,
        'match_length_minutes' => 60,
    ]);
    $competition = Competition::factory()->create([
        'game_id' => $game->id,
        'game_mode_id' => $mode->id,
        'match_length_minutes' => 90,
        'max_teams' => 8,
    ]);

    $estimate = $this->estimator->estimate($competition, [
        'stage_type' => 'single_elimination',
        'match_count' => 7,
    ]);

    expect($estimate->minutes)->toBe(7 * 90)
        ->and($competition->effectiveMatchLengthMinutes())->toBe(90);
});

it('produces stable hashes for identical inputs', function (): void {
    $a = $this->estimator->estimate($this->competition, ['stage_type' => 'single_elimination', 'match_count' => 7]);
    $b = $this->estimator->estimate($this->competition, ['stage_type' => 'single_elimination', 'match_count' => 7]);

    expect($a->hash)->toBe($b->hash);
});
