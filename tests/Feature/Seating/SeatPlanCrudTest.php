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

it('validates data is valid json', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->post('/seat-plans', [
            'name' => 'Test Plan',
            'event_id' => $event->id,
            'data' => 'not valid json',
        ])
        ->assertSessionHasErrors(['data']);
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

it('allows admins to update a seat plan', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $seatPlan = SeatPlan::factory()->create();

    $newData = json_encode([
        'blocks' => [
            [
                'id' => 'orchestra',
                'title' => 'Orchestra',
                'color' => '#2c3e50',
                'seats' => [
                    ['id' => 1, 'title' => 'A1', 'x' => 0, 'y' => 0, 'salable' => true],
                ],
                'labels' => [],
            ],
        ],
    ]);

    $this->actingAs($admin)
        ->patch("/seat-plans/{$seatPlan->id}", [
            'name' => 'Updated Hall',
            'data' => $newData,
        ])
        ->assertRedirect();

    $seatPlan->refresh();
    expect($seatPlan->name)->toBe('Updated Hall');
    expect($seatPlan->data['blocks'])->toHaveCount(1);
    expect($seatPlan->data['blocks'][0]['id'])->toBe('orchestra');
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

it('stores seat plan with default empty data when no data provided', function () {
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
    expect($seatPlan->data)->toBe(['blocks' => []]);
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
