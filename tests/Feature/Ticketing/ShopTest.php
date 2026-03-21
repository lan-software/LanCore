<?php

use App\Domain\Event\Models\Event;
use App\Domain\Ticketing\Models\TicketType;
use App\Enums\RoleName;
use App\Models\Role;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('shows the shop page with purchasable ticket types', function () {
    $event = Event::factory()->create(['status' => 'published']);
    TicketType::factory()->count(2)->create([
        'event_id' => $event->id,
        'is_hidden' => false,
        'purchase_from' => now()->subDay(),
        'purchase_until' => now()->addDay(),
    ]);

    $this->get('/shop')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('shop/Index')
                ->has('event')
                ->has('ticketTypes', 2)
        );
});

it('shows empty state when no events are published', function () {
    $this->get('/shop')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('shop/Index')
                ->where('event', null)
        );
});

it('requires authentication for cart operations', function () {
    $this->post('/cart/items', [
        'purchasable_type' => 'ticket_type',
        'purchasable_id' => 1,
        'event_id' => 1,
    ])->assertRedirect('/login');
});

it('requires authentication to view the cart', function () {
    $this->get('/cart')->assertRedirect('/login');
});
