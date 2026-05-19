<?php

use App\Domain\Competition\Models\Competition;
use App\Domain\CompetitionSchedule\Events\RoundScheduleUpdated;
use App\Domain\CompetitionSchedule\Models\CompetitionRoundSchedule;
use App\Domain\CompetitionSchedule\Models\CompetitionStageSchedule;
use App\Domain\Event\Models\Event;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Event as EventFacade;

beforeEach(function (): void {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('updates a round schedule and marks duration overridden (RND-004)', function (): void {
    EventFacade::fake([RoundScheduleUpdated::class]);
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();
    $competition = Competition::factory()->create(['event_id' => $event->id]);
    $stageSchedule = CompetitionStageSchedule::factory()->for($competition)->create();
    $round = CompetitionRoundSchedule::factory()->create([
        'stage_schedule_id' => $stageSchedule->id,
        'estimated_duration_minutes' => 30,
        'reserve_buffer_minutes' => 10,
    ]);

    $this->actingAs($admin)
        ->from('/backstage/competition-board')
        ->patch("/backstage/round-schedules/{$round->id}", [
            'estimated_duration_minutes' => 45,
            'reserve_buffer_minutes' => 15,
        ])
        ->assertRedirect('/backstage/competition-board');

    $round->refresh();
    expect($round->estimated_duration_minutes)->toBe(45)
        ->and($round->reserve_buffer_minutes)->toBe(15)
        ->and($round->duration_overridden)->toBeTrue();

    EventFacade::assertDispatched(RoundScheduleUpdated::class);
});

it('blocks non-admin users from updating a round schedule (RND-005)', function (): void {
    $user = User::factory()->withRole(RoleName::User)->create();
    $competition = Competition::factory()->create();
    $stageSchedule = CompetitionStageSchedule::factory()->for($competition)->create();
    $round = CompetitionRoundSchedule::factory()->create([
        'stage_schedule_id' => $stageSchedule->id,
    ]);

    $this->actingAs($user)
        ->patchJson("/backstage/round-schedules/{$round->id}", [
            'estimated_duration_minutes' => 90,
        ])
        ->assertForbidden();
});

it('returns Inertia-compatible redirect on round drag, not JSON', function (): void {
    EventFacade::fake([RoundScheduleUpdated::class]);
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();
    $competition = Competition::factory()->create(['event_id' => $event->id]);
    $stageSchedule = CompetitionStageSchedule::factory()->for($competition)->create();
    $round = CompetitionRoundSchedule::factory()->create(['stage_schedule_id' => $stageSchedule->id]);

    $response = $this->actingAs($admin)
        ->from('/backstage/competition-board')
        ->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => '1',
        ])
        ->patch("/backstage/round-schedules/{$round->id}", [
            'starts_at' => now()->addHours(2)->toIso8601String(),
        ]);

    $response->assertRedirect();
    expect($response->headers->get('Content-Type'))
        ->not->toContain('application/json');
});

it('resets to computed duration when reset_to_computed=true', function (): void {
    EventFacade::fake([RoundScheduleUpdated::class]);
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();
    $competition = Competition::factory()->create(['event_id' => $event->id]);
    $stageSchedule = CompetitionStageSchedule::factory()->for($competition)->create();
    $round = CompetitionRoundSchedule::factory()->overridden()->create([
        'stage_schedule_id' => $stageSchedule->id,
    ]);

    $this->actingAs($admin)
        ->from('/backstage/competition-board')
        ->patch("/backstage/round-schedules/{$round->id}", [
            'reset_to_computed' => true,
        ])
        ->assertRedirect();

    $round->refresh();
    expect($round->duration_overridden)->toBeFalse();
});
