<?php

use App\Domain\Event\Models\Event;
use App\Domain\Sponsoring\Models\Sponsor;
use App\Domain\Sponsoring\Models\SponsorLevel;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
    Role::updateOrCreate(['name' => RoleName::SponsorManager->value], ['label' => 'Sponsor Manager']);
});

it('allows admins to view the create sponsor page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/sponsors/create')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('sponsors/Create')
                ->has('sponsorLevels')
                ->has('events')
        );
});

it('allows admins to store a new sponsor', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/sponsors', [
            'name' => 'Test Sponsor',
            'description' => 'A test sponsor.',
            'link' => 'https://example.com',
        ])
        ->assertRedirect('/sponsors');

    expect(Sponsor::where('name', 'Test Sponsor')->exists())->toBeTrue();
});

it('allows admins to store a sponsor with events', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->post('/sponsors', [
            'name' => 'Event Sponsor',
            'event_ids' => [$event->id],
        ])
        ->assertRedirect('/sponsors');

    $sponsor = Sponsor::where('name', 'Event Sponsor')->first();
    expect($sponsor)->not->toBeNull();
    expect($sponsor->events)->toHaveCount(1);
    expect($sponsor->events->first()->id)->toBe($event->id);
});

it('allows admins to store a sponsor with a level', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $level = SponsorLevel::factory()->create();

    $this->actingAs($admin)
        ->post('/sponsors', [
            'name' => 'Leveled Sponsor',
            'sponsor_level_id' => $level->id,
        ])
        ->assertRedirect('/sponsors');

    $sponsor = Sponsor::where('name', 'Leveled Sponsor')->first();
    expect($sponsor->sponsor_level_id)->toBe($level->id);
});

it('validates required fields when storing a sponsor', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/sponsors', [])
        ->assertSessionHasErrors(['name']);
});

it('validates link is a valid url', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/sponsors', [
            'name' => 'Bad Link Sponsor',
            'link' => 'not-a-url',
        ])
        ->assertSessionHasErrors(['link']);
});

it('allows admins to view the edit sponsor page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $sponsor = Sponsor::factory()->create();

    $this->actingAs($admin)
        ->get("/sponsors/{$sponsor->id}")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('sponsors/Edit')
                ->has('sponsor')
                ->has('sponsorLevels')
                ->has('events')
                ->has('users')
                ->where('sponsor.id', $sponsor->id)
        );
});

it('allows admins to update a sponsor', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $sponsor = Sponsor::factory()->create();

    $this->actingAs($admin)
        ->patch("/sponsors/{$sponsor->id}", [
            'name' => 'Updated Sponsor',
            'description' => 'Updated description.',
        ])
        ->assertRedirect();

    expect($sponsor->fresh())
        ->name->toBe('Updated Sponsor')
        ->description->toBe('Updated description.');
});

it('allows admins to delete a sponsor', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $sponsor = Sponsor::factory()->create();
    $sponsorId = $sponsor->id;

    $this->actingAs($admin)
        ->delete("/sponsors/{$sponsorId}")
        ->assertRedirect('/sponsors');

    expect(Sponsor::find($sponsorId))->toBeNull();
});

it('allows sponsor managers to edit their own sponsor', function () {
    $manager = User::factory()->withRole(RoleName::SponsorManager)->create();
    $sponsor = Sponsor::factory()->create();
    $sponsor->managers()->attach($manager->id);

    $this->actingAs($manager)
        ->get("/sponsors/{$sponsor->id}")
        ->assertSuccessful();
});

it('forbids sponsor managers from editing unassigned sponsors', function () {
    $manager = User::factory()->withRole(RoleName::SponsorManager)->create();
    $sponsor = Sponsor::factory()->create();

    $this->actingAs($manager)
        ->get("/sponsors/{$sponsor->id}")
        ->assertForbidden();
});

it('forbids sponsor managers from deleting sponsors', function () {
    $manager = User::factory()->withRole(RoleName::SponsorManager)->create();
    $sponsor = Sponsor::factory()->create();
    $sponsor->managers()->attach($manager->id);

    $this->actingAs($manager)
        ->delete("/sponsors/{$sponsor->id}")
        ->assertForbidden();
});
