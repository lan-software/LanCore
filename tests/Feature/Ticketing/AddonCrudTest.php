<?php

use App\Domain\Event\Models\Event;
use App\Domain\Ticketing\Models\Addon;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows admins to view the addons index', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    Addon::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get('/ticket-addons')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('ticket-addons/Index')
                ->has('ticketAddons.data', 3)
        );
});

it('filters addons by event id', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();
    Addon::factory()->count(2)->create(['event_id' => $event->id]);
    Addon::factory()->create(); // different event

    $this->actingAs($admin)
        ->get("/ticket-addons?event_id={$event->id}")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('ticket-addons/Index')
                ->has('ticketAddons.data', 2)
        );
});

it('searches addons by name', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    Addon::factory()->create(['name' => 'Ethernet Upgrade']);
    Addon::factory()->create(['name' => 'Power Supply']);

    $this->actingAs($admin)
        ->get('/ticket-addons?search=Ethernet')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('ticket-addons/Index')
                ->has('ticketAddons.data', 1)
        );
});

it('allows admins to view the create addon page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/ticket-addons/create')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('ticket-addons/Create')
                ->has('events')
        );
});

it('allows admins to view the edit addon page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $addon = Addon::factory()->create();

    $this->actingAs($admin)
        ->get("/ticket-addons/{$addon->id}")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('ticket-addons/Edit')
                ->has('ticketAddon')
        );
});

it('allows admins to update an addon', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $addon = Addon::factory()->create();

    $this->actingAs($admin)
        ->patch("/ticket-addons/{$addon->id}", [
            'name' => 'Updated Addon',
            'price' => 2000,
            'quota' => 30,
            'seats_consumed' => 1,
            'requires_ticket' => true,
            'is_hidden' => false,
        ])
        ->assertRedirect();

    expect($addon->fresh()->name)->toBe('Updated Addon');
    expect($addon->fresh()->price)->toBe(2000);
});

it('validates required fields when storing an addon', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/ticket-addons', [])
        ->assertSessionHasErrors(['name', 'price', 'event_id']);
});

it('validates required fields when updating an addon', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $addon = Addon::factory()->create();

    $this->actingAs($admin)
        ->patch("/ticket-addons/{$addon->id}", [])
        ->assertSessionHasErrors(['name', 'price', 'seats_consumed']);
});

it('denies regular users access to addons', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/ticket-addons')
        ->assertForbidden();
});

it('denies regular users from creating addons', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create();

    $this->actingAs($user)
        ->post('/ticket-addons', [
            'name' => 'Test',
            'price' => 100,
            'event_id' => $event->id,
            'seats_consumed' => 0,
        ])
        ->assertForbidden();
});

it('denies unauthenticated users access to addons', function () {
    $this->get('/ticket-addons')
        ->assertRedirect('/login');
});

it('paginates addons with custom per_page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    Addon::factory()->count(15)->create();

    $this->actingAs($admin)
        ->get('/ticket-addons?per_page=10')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('ticket-addons/Index')
                ->has('ticketAddons.data', 10)
        );
});

it('sorts addons by name', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    Addon::factory()->create(['name' => 'Zebra']);
    Addon::factory()->create(['name' => 'Alpha']);

    $this->actingAs($admin)
        ->get('/ticket-addons?sort=name&direction=asc')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('ticket-addons/Index')
                ->where('ticketAddons.data.0.name', 'Alpha')
        );
});
