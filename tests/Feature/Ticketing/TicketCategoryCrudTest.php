<?php

use App\Domain\Event\Models\Event;
use App\Domain\Ticketing\Models\TicketCategory;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows admins to view the ticket categories index', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    TicketCategory::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get('/ticket-categories')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('ticket-categories/Index')
                ->has('ticketCategories.data', 3)
        );
});

it('filters categories by event id', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();
    TicketCategory::factory()->count(2)->create(['event_id' => $event->id]);
    TicketCategory::factory()->create(); // different event

    $this->actingAs($admin)
        ->get("/ticket-categories?event_id={$event->id}")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('ticket-categories/Index')
                ->has('ticketCategories.data', 2)
        );
});

it('searches categories by name', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    TicketCategory::factory()->create(['name' => 'Premium Zone']);
    TicketCategory::factory()->create(['name' => 'Standard Area']);

    $this->actingAs($admin)
        ->get('/ticket-categories?search=Premium')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('ticket-categories/Index')
                ->has('ticketCategories.data', 1)
        );
});

it('allows admins to view the create category page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/ticket-categories/create')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('ticket-categories/Create')
                ->has('events')
        );
});

it('allows admins to view the edit category page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $category = TicketCategory::factory()->create();

    $this->actingAs($admin)
        ->get("/ticket-categories/{$category->id}")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('ticket-categories/Edit')
                ->has('ticketCategory')
        );
});

it('validates required fields when storing a category', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/ticket-categories', [])
        ->assertSessionHasErrors(['name']);
});

it('denies regular users access to categories', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/ticket-categories')
        ->assertForbidden();
});

it('denies regular users from creating categories', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->post('/ticket-categories', [
            'name' => 'Test Category',
            'sort_order' => 1,
        ])
        ->assertForbidden();
});

it('denies unauthenticated users access to categories', function () {
    $this->get('/ticket-categories')
        ->assertRedirect('/login');
});

it('paginates categories with custom per_page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    TicketCategory::factory()->count(15)->create();

    $this->actingAs($admin)
        ->get('/ticket-categories?per_page=10')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('ticket-categories/Index')
                ->has('ticketCategories.data', 10)
        );
});

it('sorts categories by sort_order', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    TicketCategory::factory()->create(['name' => 'Second', 'sort_order' => 2]);
    TicketCategory::factory()->create(['name' => 'First', 'sort_order' => 1]);

    $this->actingAs($admin)
        ->get('/ticket-categories?sort=sort_order&direction=asc')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('ticket-categories/Index')
                ->where('ticketCategories.data.0.name', 'First')
        );
});
