<?php

use App\Domain\Ticketing\Models\TicketType;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows admins to view the audit page for a ticket type', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $ticketType = TicketType::factory()->create();

    $this->actingAs($admin)
        ->get("/ticket-types/{$ticketType->id}/audit")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('ticket-types/Audit')
                ->has('ticketType')
                ->has('audits.data')
        );
});

it('denies non-admin users access to the ticket type audit page', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $ticketType = TicketType::factory()->create();

    $this->actingAs($user)
        ->get("/ticket-types/{$ticketType->id}/audit")
        ->assertForbidden();
});

it('redirects unauthenticated users to login for ticket type audit', function () {
    $ticketType = TicketType::factory()->create();

    $this->get("/ticket-types/{$ticketType->id}/audit")
        ->assertRedirect('/login');
});
