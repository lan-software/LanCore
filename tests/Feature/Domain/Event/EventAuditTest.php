<?php

use App\Domain\Event\Models\Event;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows admins to view the audit page for an event', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->get("/events/{$event->id}/audit")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('events/Audit')
                ->has('event')
                ->has('audits.data')
        );
});

it('denies non-admin users access to the audit page', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create();

    $this->actingAs($user)
        ->get("/events/{$event->id}/audit")
        ->assertForbidden();
});

it('redirects unauthenticated users to login', function () {
    $event = Event::factory()->create();

    $this->get("/events/{$event->id}/audit")
        ->assertRedirect('/login');
});

it('records audit entries when an event is created', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/events', [
            'name' => 'Test LAN Party',
            'start_date' => '2026-07-01 10:00:00',
            'end_date' => '2026-07-03 18:00:00',
        ])
        ->assertRedirect('/events');

    $event = Event::where('name', 'Test LAN Party')->first();

    $this->actingAs($admin)
        ->get("/events/{$event->id}/audit")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('events/Audit')
                ->where('audits.total', 1)
                ->where('audits.data.0.event', 'created')
        );
});

it('records audit entries when an event is updated', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/events', [
            'name' => 'Original Name',
            'start_date' => '2026-07-01 10:00:00',
            'end_date' => '2026-07-03 18:00:00',
        ])
        ->assertRedirect('/events');

    $event = Event::where('name', 'Original Name')->first();

    $this->actingAs($admin)
        ->patch("/events/{$event->id}", [
            'name' => 'Updated Name',
            'start_date' => '2026-07-01 10:00:00',
            'end_date' => '2026-07-03 18:00:00',
        ]);

    $this->actingAs($admin)
        ->get("/events/{$event->id}/audit")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->where('audits.total', 2)
                ->where('audits.data.0.event', 'updated')
                ->where('audits.data.1.event', 'created')
        );
});
