<?php

use App\Domain\Event\Models\Event;
use App\Domain\Seating\Models\SeatPlan;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows admins to view the seat plans index', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/seat-plans')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('seating/Index'));
});

it('allows admins to view the create seat plan page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/seat-plans/create')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('seating/Create')->has('events'));
});

it('allows admins to store a new seat plan', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->post('/seat-plans', [
            'name' => 'Main Hall',
            'event_id' => $event->id,
            'data' => json_encode(['blocks' => []]),
        ])
        ->assertRedirect('/seat-plans');

    expect(SeatPlan::where('name', 'Main Hall')->exists())->toBeTrue();
});

it('validates required fields when storing a seat plan', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/seat-plans', [])
        ->assertSessionHasErrors(['name', 'event_id']);
});

it('validates event exists when storing a seat plan', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/seat-plans', [
            'name' => 'Test Plan',
            'event_id' => 99999,
        ])
        ->assertSessionHasErrors(['event_id']);
});

it('allows admins to view the edit seat plan page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $seatPlan = SeatPlan::factory()->create();

    $this->actingAs($admin)
        ->get("/seat-plans/{$seatPlan->id}")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('seating/Edit')
                ->has('seatPlan')
                ->where('seatPlan.id', $seatPlan->id)
        );
});

it('allows admins to update a seat plan with a normalized block payload', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $seatPlan = SeatPlan::factory()->empty()->create();

    $newData = json_encode([
        'blocks' => [[
            'title' => 'Orchestra',
            'color' => '#2c3e50',
            'seat_title_prefix' => 'VIP-',
            'sort_order' => 0,
            'rows' => [[
                'id' => 'new-row-a',
                'name' => 'A',
                'sort_order' => 0,
            ]],
            'seats' => [[
                'row_id' => 'new-row-a',
                'number' => 1,
                'title' => 'A1',
                'x' => 0,
                'y' => 0,
                'salable' => true,
            ]],
            'labels' => [],
            'allowed_ticket_category_ids' => [],
        ]],
    ]);

    $response = $this->actingAs($admin)
        ->patch("/seat-plans/{$seatPlan->id}", [
            'name' => 'Updated Hall',
            'data' => $newData,
        ])
        ->assertRedirect()
        ->assertSessionHas('status', 'seat-plan-updated')
        ->assertSessionHas('id_map');

    $seatPlan->refresh();
    expect($seatPlan->name)->toBe('Updated Hall');
    $blocks = $seatPlan->blocks()->with('seats')->get();
    expect($blocks)->toHaveCount(1);
    expect($blocks[0]->title)->toBe('Orchestra');
    expect($blocks[0]->seat_title_prefix)->toBe('VIP-');
    expect($blocks[0]->seats)->toHaveCount(1);
    expect($blocks[0]->seats->first()->title)->toBe('A1');

    $idMap = $response->getSession()->get('id_map');
    expect($idMap)->toHaveKey('blocks')
        ->and($idMap)->toHaveKey('rows')
        ->and($idMap)->toHaveKey('seats');
});

it('allows admins to delete a seat plan', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $seatPlan = SeatPlan::factory()->create();

    $this->actingAs($admin)
        ->delete("/seat-plans/{$seatPlan->id}")
        ->assertRedirect('/seat-plans');

    expect(SeatPlan::find($seatPlan->id))->toBeNull();
});

it('forbids regular users from accessing seat plans', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/seat-plans')
        ->assertForbidden();
});

it('forbids regular users from creating seat plans', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create();

    $this->actingAs($user)
        ->post('/seat-plans', [
            'name' => 'Test',
            'event_id' => $event->id,
        ])
        ->assertForbidden();
});

it('stores seat plan with no blocks when data is omitted', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->post('/seat-plans', [
            'name' => 'Empty Plan',
            'event_id' => $event->id,
        ])
        ->assertRedirect('/seat-plans');

    $seatPlan = SeatPlan::where('name', 'Empty Plan')->first();
    expect($seatPlan)->not->toBeNull();
    expect($seatPlan->blocks()->count())->toBe(0);
});

it('allows searching seat plans by name', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    SeatPlan::factory()->create(['name' => 'Main Hall']);
    SeatPlan::factory()->create(['name' => 'Balcony']);

    $this->actingAs($admin)
        ->get('/seat-plans?search=Main')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('seating/Index')
                ->where('seatPlans.total', 1)
        );
});
