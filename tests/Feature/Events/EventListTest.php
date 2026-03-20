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

it('returns paginated events for admins', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    Event::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get('/events')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('events/Index')
                ->has('events.data')
                ->has('events.total')
                ->has('filters')
        );
});

it('filters events by search term', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    Event::factory()->create(['name' => 'Summer LAN']);
    Event::factory()->create(['name' => 'Winter Meetup']);

    $this->actingAs($admin)
        ->get('/events?search=summer')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('events/Index')
                ->where('events.total', 1)
                ->where('events.data.0.name', 'Summer LAN')
        );
});

it('filters events by status', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    Event::factory()->create(['status' => 'draft']);
    Event::factory()->create(['status' => 'published']);

    $this->actingAs($admin)
        ->get('/events?status=published')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('events/Index')
                ->where('events.total', 1)
        );
});

it('sorts events by start_date ascending', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    Event::factory()->create(['name' => 'Later Event', 'start_date' => '2026-12-01 10:00:00']);
    Event::factory()->create(['name' => 'Earlier Event', 'start_date' => '2026-06-01 10:00:00']);

    $this->actingAs($admin)
        ->get('/events?sort=start_date&direction=asc')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('events/Index')
                ->where('events.data.0.name', 'Earlier Event')
        );
});
