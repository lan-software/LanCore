<?php

use App\Domain\Ticketing\Models\TicketCategory;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows admins to view the audit page for a ticket category', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $ticketCategory = TicketCategory::factory()->create();

    $this->actingAs($admin)
        ->get("/ticket-categories/{$ticketCategory->id}/audit")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('ticket-categories/Audit')
                ->has('ticketCategory')
                ->has('audits.data')
        );
});

it('denies non-admin users access to the ticket category audit page', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $ticketCategory = TicketCategory::factory()->create();

    $this->actingAs($user)
        ->get("/ticket-categories/{$ticketCategory->id}/audit")
        ->assertForbidden();
});

it('redirects unauthenticated users to login for ticket category audit', function () {
    $ticketCategory = TicketCategory::factory()->create();

    $this->get("/ticket-categories/{$ticketCategory->id}/audit")
        ->assertRedirect('/login');
});
