<?php

use App\Domain\Event\Models\Event;
use App\Domain\Ticketing\Models\Addon;
use App\Domain\Ticketing\Models\TicketCategory;
use App\Domain\Ticketing\Models\TicketType;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

// ---- Ticket Types ----

it('allows admins to view ticket types index', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    TicketType::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get('/ticket-types')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('ticket-types/Index')
                ->has('ticketTypes.data', 3)
        );
});

it('allows admins to view the create ticket type page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/ticket-types/create')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('ticket-types/Create')
                ->has('events')
                ->has('categories')
                ->has('groups')
        );
});

it('allows admins to store a new ticket type', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->post('/ticket-types', [
            'name' => 'Premium Ticket',
            'description' => 'Best seat in the house',
            'price' => 5000,
            'quota' => 100,
            'seats_per_user' => 1,
            'is_seatable' => true,
            'is_hidden' => false,
            'event_id' => $event->id,
        ])
        ->assertRedirect('/ticket-types');

    expect(TicketType::where('name', 'Premium Ticket')->exists())->toBeTrue();
});

it('validates required fields when storing a ticket type', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/ticket-types', [])
        ->assertSessionHasErrors(['name', 'price', 'quota', 'event_id']);
});

it('allows admins to update a ticket type', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $ticketType = TicketType::factory()->create();

    $this->actingAs($admin)
        ->patch("/ticket-types/{$ticketType->id}", [
            'name' => 'Updated Ticket Type',
            'price' => 6000,
            'quota' => 200,
            'seats_per_user' => 2,
            'is_seatable' => true,
            'is_hidden' => false,
            'event_id' => $ticketType->event_id,
        ])
        ->assertRedirect();

    expect($ticketType->fresh()->name)->toBe('Updated Ticket Type');
});

it('prevents updating locked fields on a locked ticket type', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $ticketType = TicketType::factory()->locked()->create(['price' => 5000]);

    $this->actingAs($admin)
        ->patch("/ticket-types/{$ticketType->id}", [
            'name' => 'Changed Name',
            'description' => null,
            'price' => 9999,
            'quota' => 999,
            'seats_per_user' => 5,
            'is_seatable' => false,
            'is_hidden' => false,
            'event_id' => $ticketType->event_id,
        ])
        ->assertRedirect();

    $updated = $ticketType->fresh();
    expect($updated->name)->toBe('Changed Name');
    expect($updated->price)->toBe(5000); // Should remain unchanged (locked)
});

it('allows admins to delete a ticket type', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $ticketType = TicketType::factory()->create();

    $this->actingAs($admin)
        ->delete("/ticket-types/{$ticketType->id}")
        ->assertRedirect('/ticket-types');

    expect(TicketType::find($ticketType->id))->toBeNull();
});

it('denies regular users access to ticket types', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/ticket-types')
        ->assertForbidden();
});

// ---- Ticket Categories ----

it('allows admins to store a ticket category', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/ticket-categories', [
            'name' => 'Premium',
            'description' => 'Premium seating area',
            'sort_order' => 1,
        ])
        ->assertRedirect('/ticket-categories');

    expect(TicketCategory::where('name', 'Premium')->exists())->toBeTrue();
});

it('allows admins to update a ticket category', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $category = TicketCategory::factory()->create();

    $this->actingAs($admin)
        ->patch("/ticket-categories/{$category->id}", [
            'name' => 'Updated Category',
            'sort_order' => 5,
        ])
        ->assertRedirect();

    expect($category->fresh()->name)->toBe('Updated Category');
});

it('allows admins to delete a ticket category', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $category = TicketCategory::factory()->create();

    $this->actingAs($admin)
        ->delete("/ticket-categories/{$category->id}")
        ->assertRedirect('/ticket-categories');

    expect(TicketCategory::find($category->id))->toBeNull();
});

// ---- Ticket Addons ----

it('allows admins to store a ticket addon', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->post('/ticket-addons', [
            'name' => '2.5 Gbit Ethernet',
            'description' => 'High-speed network upgrade',
            'price' => 1500,
            'quota' => 50,
            'seats_consumed' => 0,
            'requires_ticket' => true,
            'is_hidden' => false,
            'event_id' => $event->id,
        ])
        ->assertRedirect('/ticket-addons');

    expect(Addon::where('name', '2.5 Gbit Ethernet')->exists())->toBeTrue();
});

it('allows admins to delete a ticket addon', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $addon = Addon::factory()->create();

    $this->actingAs($admin)
        ->delete("/ticket-addons/{$addon->id}")
        ->assertRedirect('/ticket-addons');

    expect(Addon::find($addon->id))->toBeNull();
});
