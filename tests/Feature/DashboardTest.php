<?php

use App\Domain\Event\Models\Event;
use App\Domain\Games\Models\Game;
use App\Domain\Games\Models\GameMode;
use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Shop\Models\Order;
use App\Domain\Shop\Models\Voucher;
use App\Domain\Sponsoring\Models\Sponsor;
use App\Domain\Sponsoring\Models\SponsorLevel;
use App\Domain\Ticketing\Models\Addon;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketType;
use App\Domain\Venue\Models\Venue;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('non-admin users see dashboard without stats', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Dashboard')
                ->where('isAdmin', false)
                ->where('stats', [])
        );
});

test('admin users see dashboard with stats', function () {
    $adminRole = Role::factory()->create(['name' => RoleName::Admin]);
    $admin = User::factory()->create();
    $admin->roles()->attach($adminRole);

    $this->actingAs($admin);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Dashboard')
                ->where('isAdmin', true)
                ->has('stats.counts')
                ->has('stats.events')
                ->has('stats.tickets')
                ->has('stats.orders')
                ->has('stats.roles')
                ->has('stats.lastActiveUsers')
        );
});

test('admin dashboard shows correct entity counts', function () {
    $adminRole = Role::factory()->create(['name' => RoleName::Admin]);
    $admin = User::factory()->create();
    $admin->roles()->attach($adminRole);

    User::factory()->count(3)->create();
    $venue = Venue::factory()->create();
    $event = Event::factory()->for($venue)->create();
    Event::factory()->for($venue)->create();
    $level = SponsorLevel::factory()->create();
    Sponsor::factory()->count(2)->for($level, 'sponsorLevel')->create();
    $ticketType = TicketType::factory()->for($event)->create();
    Addon::factory()->for($event)->create();
    $order = Order::factory()->for($event)->for($admin)->create();
    Ticket::factory()->for($event)->for($ticketType)->for($order)->for($admin, 'owner')->create();
    Game::factory()->create();
    GameMode::factory()->create();
    SeatPlan::factory()->for($event)->create();
    Voucher::factory()->create();

    $this->actingAs($admin);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Dashboard')
                ->where('stats.counts.users', 4)
                ->where('stats.counts.events', 2)
                ->where('stats.counts.venues', 1)
                ->where('stats.counts.sponsors', 2)
                ->where('stats.counts.sponsor_levels', 1)
                ->where('stats.counts.tickets', 1)
                ->where('stats.counts.ticket_types', 1)
                ->where('stats.counts.addons', 1)
                ->where('stats.counts.orders', 1)
                ->where('stats.counts.games', 2)
                ->where('stats.counts.game_modes', 1)
                ->where('stats.counts.seat_plans', 1)
                ->where('stats.counts.vouchers', 1)
        );
});

test('admin dashboard shows event breakdown', function () {
    $adminRole = Role::factory()->create(['name' => RoleName::Admin]);
    $admin = User::factory()->create();
    $admin->roles()->attach($adminRole);

    Event::factory()->published()->create([
        'start_date' => now()->addDays(7),
        'end_date' => now()->addDays(8),
    ]);
    Event::factory()->create([
        'start_date' => now()->subDays(3),
        'end_date' => now()->subDays(1),
    ]);

    $this->actingAs($admin);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Dashboard')
                ->where('stats.events.upcoming', 1)
                ->where('stats.events.past', 1)
                ->where('stats.events.published', 1)
                ->where('stats.events.draft', 1)
        );
});

test('admin dashboard shows role distribution', function () {
    $adminRole = Role::factory()->create(['name' => RoleName::Admin]);
    $sponsorManagerRole = Role::factory()->create(['name' => RoleName::SponsorManager]);

    $admin = User::factory()->create();
    $admin->roles()->attach($adminRole);

    $manager = User::factory()->create();
    $manager->roles()->attach($sponsorManagerRole);

    $this->actingAs($admin);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Dashboard')
                ->where('stats.roles.admin', 1)
                ->where('stats.roles.sponsor_manager', 1)
        );
});

test('admin dashboard shows ticket status breakdown', function () {
    $adminRole = Role::factory()->create(['name' => RoleName::Admin]);
    $admin = User::factory()->create();
    $admin->roles()->attach($adminRole);

    Ticket::factory()->count(3)->create();
    Ticket::factory()->count(2)->checkedIn()->create();
    Ticket::factory()->cancelled()->create();

    $this->actingAs($admin);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Dashboard')
                ->where('stats.tickets.active', 3)
                ->where('stats.tickets.checked_in', 2)
                ->where('stats.tickets.cancelled', 1)
        );
});

test('admin dashboard shows order status breakdown and revenue', function () {
    $adminRole = Role::factory()->create(['name' => RoleName::Admin]);
    $admin = User::factory()->create();
    $admin->roles()->attach($adminRole);

    Order::factory()->count(2)->create(['total' => 5000]);
    Order::factory()->pending()->create();
    Order::factory()->failed()->create();

    $this->actingAs($admin);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Dashboard')
                ->where('stats.orders.completed', 2)
                ->where('stats.orders.pending', 1)
                ->where('stats.orders.failed', 1)
                ->where('stats.orders.refunded', 0)
                ->where('stats.orders.total_revenue', 10000)
        );
});
