<?php

use App\Domain\Competition\Models\Competition;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
    Queue::fake();
});

it('allows admins to view the competitions index', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/competitions')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('competitions/Index'));
});

it('allows admins to view the create competition page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/competitions/create')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('competitions/Create'));
});

it('allows admins to store a new competition', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/competitions', [
            'name' => 'CS2 Tournament',
            'type' => 'tournament',
            'stage_type' => 'single_elimination',
            'team_size' => 5,
            'max_teams' => 16,
        ])
        ->assertRedirect('/competitions');

    expect(Competition::where('name', 'CS2 Tournament')->exists())->toBeTrue();
});

it('validates required fields when storing a competition', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/competitions', [])
        ->assertSessionHasErrors(['name', 'type', 'stage_type']);
});

it('allows admins to view the edit competition page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $competition = Competition::factory()->create();

    $this->actingAs($admin)
        ->get("/competitions/{$competition->id}/edit")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('competitions/Edit')
                ->has('competition')
                ->where('competition.id', $competition->id)
        );
});

it('allows admins to update a competition', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $competition = Competition::factory()->create();

    $this->actingAs($admin)
        ->patch("/competitions/{$competition->id}", [
            'name' => 'Updated Tournament',
        ])
        ->assertRedirect();

    expect($competition->fresh()->name)->toBe('Updated Tournament');
});

it('allows admins to transition competition status', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $competition = Competition::factory()->create(['status' => 'draft']);

    $this->actingAs($admin)
        ->patch("/competitions/{$competition->id}", [
            'status' => 'registration_open',
        ])
        ->assertRedirect();

    expect($competition->fresh()->status->value)->toBe('registration_open');
});

it('rejects invalid status transitions', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $competition = Competition::factory()->create(['status' => 'draft']);

    $this->actingAs($admin)
        ->patch("/competitions/{$competition->id}", [
            'status' => 'running',
        ])
        ->assertSessionHasErrors('status');
});

it('allows admins to delete a competition', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $competition = Competition::factory()->create();

    $this->actingAs($admin)
        ->delete("/competitions/{$competition->id}")
        ->assertRedirect('/competitions');

    expect(Competition::find($competition->id))->toBeNull();
});

it('forbids users from creating competitions', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->post('/competitions', [
            'name' => 'Test',
            'type' => 'tournament',
            'stage_type' => 'single_elimination',
        ])
        ->assertForbidden();
});

it('forbids users from deleting competitions', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $competition = Competition::factory()->create();

    $this->actingAs($user)
        ->delete("/competitions/{$competition->id}")
        ->assertForbidden();
});
