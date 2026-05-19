<?php

use App\Domain\Competition\Models\Competition;
use App\Domain\CompetitionSchedule\Events\StageScheduleUpdated;
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

it('updates a stage schedule and marks duration overridden', function (): void {
    EventFacade::fake([StageScheduleUpdated::class]);
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();
    $competition = Competition::factory()->create(['event_id' => $event->id]);
    $schedule = CompetitionStageSchedule::factory()->for($competition)->create([
        'estimated_duration_minutes' => 60,
        'reserve_buffer_minutes' => 15,
    ]);

    $this->actingAs($admin)
        ->patchJson("/backstage/stage-schedules/{$schedule->id}", [
            'estimated_duration_minutes' => 90,
            'reserve_buffer_minutes' => 30,
        ])
        ->assertOk();

    $schedule->refresh();
    expect($schedule->estimated_duration_minutes)->toBe(90)
        ->and($schedule->reserve_buffer_minutes)->toBe(30)
        ->and($schedule->duration_overridden)->toBeTrue();

    EventFacade::assertDispatched(StageScheduleUpdated::class);
});

it('blocks non-admin users from updating a stage schedule', function (): void {
    $user = User::factory()->withRole(RoleName::User)->create();
    $competition = Competition::factory()->create();
    $schedule = CompetitionStageSchedule::factory()->for($competition)->create();

    $this->actingAs($user)
        ->patchJson("/backstage/stage-schedules/{$schedule->id}", [
            'estimated_duration_minutes' => 90,
        ])
        ->assertForbidden();
});

it('renders the competition board for admins via the session-scoped route', function (): void {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();
    $competition = Competition::factory()->create(['event_id' => $event->id]);
    CompetitionStageSchedule::factory()->for($competition)->create();

    $this->actingAs($admin)
        ->withSession(['selected_event_id' => $event->id])
        ->get('/backstage/competition-board')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('events/CompetitionBoard'));
});
