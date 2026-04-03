<?php

use App\Domain\Competition\Models\Competition;
use App\Domain\Event\Models\Event;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('passes selectedEventId to competition create page when event context is set', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->withSession(['selected_event_id' => $event->id])
        ->get('/competitions/create')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('competitions/Create')
                ->where('selectedEventId', $event->id)
        );
});

it('passes null selectedEventId to competition create page when no event context is set', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/competitions/create')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('competitions/Create')
                ->where('selectedEventId', null)
        );
});

it('filters competitions index by session event context', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event1 = Event::factory()->create();
    $event2 = Event::factory()->create();

    Competition::factory()->for($event1)->create(['name' => 'Event1 Competition']);
    Competition::factory()->for($event2)->create(['name' => 'Event2 Competition']);

    $this->actingAs($admin)
        ->withSession(['selected_event_id' => $event1->id])
        ->get('/competitions')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('competitions/Index')
                ->where('competitions.total', 1)
                ->where('competitions.data.0.name', 'Event1 Competition')
        );
});

it('shows all competitions when no event context is set', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event1 = Event::factory()->create();
    $event2 = Event::factory()->create();

    Competition::factory()->for($event1)->create();
    Competition::factory()->for($event2)->create();

    $this->actingAs($admin)
        ->get('/competitions')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('competitions/Index')
                ->where('competitions.total', 2)
        );
});
