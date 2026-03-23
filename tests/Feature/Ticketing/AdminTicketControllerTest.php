<?php

use App\Domain\Ticketing\Models\Ticket;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows admins to view admin tickets index', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    Ticket::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get('/admin-tickets')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin-tickets/Index')
                ->has('tickets.data', 3)
        );
});

it('allows admins to search tickets by validation id', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $ticket = Ticket::factory()->create(['validation_id' => 'UNIQUEVALID12345']);
    Ticket::factory()->count(2)->create();

    $this->actingAs($admin)
        ->get('/admin-tickets?search=UNIQUEVALID12345')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin-tickets/Index')
                ->has('tickets.data', 1)
        );
});

it('allows admins to filter tickets by status', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    Ticket::factory()->count(2)->create();
    Ticket::factory()->cancelled()->create();

    $this->actingAs($admin)
        ->get('/admin-tickets?status=cancelled')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin-tickets/Index')
                ->has('tickets.data', 1)
        );
});

it('allows admins to view a ticket detail', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $ticket = Ticket::factory()->create();

    $this->actingAs($admin)
        ->get("/admin-tickets/{$ticket->id}")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin-tickets/Show')
                ->has('ticket')
                ->where('ticket.id', $ticket->id)
        );
});

it('denies non-admin users from viewing admin tickets index', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/admin-tickets')
        ->assertForbidden();
});

it('denies non-admin users from viewing admin ticket detail', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $ticket = Ticket::factory()->create();

    $this->actingAs($user)
        ->get("/admin-tickets/{$ticket->id}")
        ->assertForbidden();
});
