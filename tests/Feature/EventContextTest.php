<?php

use App\Domain\Event\Models\Event;
use App\Domain\Program\Models\Program;
use App\Domain\Seating\Models\SeatPlan;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

// --- EventContextController Tests ---

it('allows admins to set event context', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->post('/event-context', ['event_id' => $event->id])
        ->assertRedirect();

    expect(session('selected_event_id'))->toBe($event->id);
});

it('validates event_id is required when storing event context', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/event-context', [])
        ->assertSessionHasErrors(['event_id']);
});

it('validates event_id exists when storing event context', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/event-context', ['event_id' => 99999])
        ->assertSessionHasErrors(['event_id']);
});

it('allows admins to clear event context', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->withSession(['selected_event_id' => $event->id])
        ->delete('/event-context')
        ->assertRedirect();

    expect(session('selected_event_id'))->toBeNull();
});

it('requires authentication for event context routes', function () {
    $this->post('/event-context', ['event_id' => 1])->assertRedirect('/login');
    $this->delete('/event-context')->assertRedirect('/login');
});

// --- Inertia Shared Props Tests ---

it('shares eventContext for admin users', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    Event::factory()->create();

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->has('eventContext')
                ->has('eventContext.events')
                ->where('eventContext.selectedEventId', null)
        );
});

it('shares selected event in eventContext when session is set', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->withSession(['selected_event_id' => $event->id])
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->where('eventContext.selectedEventId', $event->id)
                ->where('eventContext.selectedEvent.id', $event->id)
                ->where('eventContext.selectedEvent.name', $event->name)
        );
});

it('does not share eventContext for regular users', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page->where('eventContext', null)
        );
});

it('clears invalid event from session when event no longer exists', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->withSession(['selected_event_id' => 99999])
        ->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->where('eventContext.selectedEventId', null)
                ->where('eventContext.selectedEvent', null)
        );
});

// --- Session-Based Index Filtering Tests ---

it('filters programs index by session event context including null event_id', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event1 = Event::factory()->create();
    $event2 = Event::factory()->create();

    Program::factory()->for($event1)->create(['name' => 'Event1 Program']);
    Program::factory()->for($event2)->create(['name' => 'Event2 Program']);

    $this->actingAs($admin)
        ->withSession(['selected_event_id' => $event1->id])
        ->get('/programs')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('programs/Index')
                ->where('programs.total', 1)
                ->where('programs.data.0.name', 'Event1 Program')
        );
});

it('explicit event_id param overrides session event context', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event1 = Event::factory()->create();
    $event2 = Event::factory()->create();

    Program::factory()->for($event1)->create(['name' => 'Event1 Program']);
    Program::factory()->for($event2)->create(['name' => 'Event2 Program']);

    $this->actingAs($admin)
        ->withSession(['selected_event_id' => $event1->id])
        ->get('/programs?event_id='.$event2->id)
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('programs/Index')
                ->where('programs.total', 1)
                ->where('programs.data.0.name', 'Event2 Program')
        );
});

it('filters seat plans index by session event context', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event1 = Event::factory()->create();
    $event2 = Event::factory()->create();

    SeatPlan::factory()->for($event1)->create(['name' => 'Event1 Plan']);
    SeatPlan::factory()->for($event2)->create(['name' => 'Event2 Plan']);

    $this->actingAs($admin)
        ->withSession(['selected_event_id' => $event1->id])
        ->get('/seat-plans')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('seating/Index')
                ->where('seatPlans.total', 1)
                ->where('seatPlans.data.0.name', 'Event1 Plan')
        );
});

it('shows all data when no event context is set', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event1 = Event::factory()->create();
    $event2 = Event::factory()->create();

    Program::factory()->for($event1)->create();
    Program::factory()->for($event2)->create();

    $this->actingAs($admin)
        ->get('/programs')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('programs/Index')
                ->where('programs.total', 2)
        );
});

// --- Create page pre-fill tests ---

it('passes selectedEventId to program create page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->withSession(['selected_event_id' => $event->id])
        ->get('/programs/create')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('programs/Create')
                ->where('selectedEventId', $event->id)
        );
});

it('passes selectedEventId to seat plan create page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->withSession(['selected_event_id' => $event->id])
        ->get('/seat-plans/create')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('seating/Create')
                ->where('selectedEventId', $event->id)
        );
});

it('passes null selectedEventId when no event context is set', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/programs/create')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('programs/Create')
                ->where('selectedEventId', null)
        );
});

it('passes selectedEventId to sponsor create page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->withSession(['selected_event_id' => $event->id])
        ->get('/sponsors/create')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('sponsors/Create')
                ->where('selectedEventId', $event->id)
        );
});

it('passes null selectedEventId to sponsor create page when no event context is set', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/sponsors/create')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('sponsors/Create')
                ->where('selectedEventId', null)
        );
});
