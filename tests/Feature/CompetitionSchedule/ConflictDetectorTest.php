<?php

use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\CompetitionTeam;
use App\Domain\Competition\Models\CompetitionTeamMember;
use App\Domain\CompetitionSchedule\Models\CompetitionStageSchedule;
use App\Domain\CompetitionSchedule\Services\ConflictDetector;
use App\Domain\Event\Models\Event;
use App\Models\User;

it('flags player double-booking when shared player overlaps', function (): void {
    $event = Event::factory()->create([
        'start_date' => now(),
        'end_date' => now()->addDays(2),
    ]);

    $compA = Competition::factory()->create(['event_id' => $event->id]);
    $compB = Competition::factory()->create(['event_id' => $event->id]);

    $user = User::factory()->create();
    $teamA = CompetitionTeam::factory()->create(['competition_id' => $compA->id]);
    $teamB = CompetitionTeam::factory()->create(['competition_id' => $compB->id]);
    CompetitionTeamMember::factory()->create(['team_id' => $teamA->id, 'user_id' => $user->id]);
    CompetitionTeamMember::factory()->create(['team_id' => $teamB->id, 'user_id' => $user->id]);

    $start = now()->addHour();
    CompetitionStageSchedule::factory()->for($compA)->create([
        'starts_at' => $start,
        'estimated_duration_minutes' => 120,
        'reserve_buffer_minutes' => 15,
    ]);
    CompetitionStageSchedule::factory()->for($compB)->create([
        'starts_at' => $start->copy()->addMinutes(30),
        'estimated_duration_minutes' => 120,
        'reserve_buffer_minutes' => 15,
    ]);

    $conflicts = (new ConflictDetector)->detectForEvent($event);

    expect($conflicts->contains(fn ($c) => $c->messageKey === 'competition_board.conflicts.player_double_booked'))->toBeTrue();
});

it('flags stages running outside the event window', function (): void {
    $event = Event::factory()->create([
        'start_date' => now(),
        'end_date' => now()->addDay(),
    ]);
    $comp = Competition::factory()->create(['event_id' => $event->id]);

    CompetitionStageSchedule::factory()->for($comp)->create([
        'starts_at' => now()->addDays(3),
        'estimated_duration_minutes' => 60,
        'reserve_buffer_minutes' => 0,
    ]);

    $conflicts = (new ConflictDetector)->detectForEvent($event);

    expect($conflicts->contains(fn ($c) => $c->messageKey === 'competition_board.conflicts.out_of_event_window'))->toBeTrue();
});

it('flags sequence overlap inside one competition', function (): void {
    $event = Event::factory()->create([
        'start_date' => now()->subDay(),
        'end_date' => now()->addDays(5),
    ]);
    $comp = Competition::factory()->create(['event_id' => $event->id]);

    CompetitionStageSchedule::factory()->for($comp)->create([
        'sequence' => 1,
        'starts_at' => now()->addHour(),
        'estimated_duration_minutes' => 60,
        'reserve_buffer_minutes' => 15,
    ]);
    CompetitionStageSchedule::factory()->for($comp)->create([
        'sequence' => 2,
        'starts_at' => now()->addMinutes(30),
        'estimated_duration_minutes' => 60,
        'reserve_buffer_minutes' => 15,
    ]);

    $conflicts = (new ConflictDetector)->detectForEvent($event);

    expect($conflicts->contains(fn ($c) => $c->messageKey === 'competition_board.conflicts.stage_sequence_overlap'))->toBeTrue();
});
