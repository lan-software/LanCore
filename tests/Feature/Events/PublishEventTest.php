<?php

use App\Domain\Event\Enums\EventStatus;
use App\Domain\Event\Models\Event;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows admins to publish a complete event', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->patch("/events/{$event->id}/publish")
        ->assertRedirect();

    expect($event->fresh()->status)->toBe(EventStatus::Published);
});

it('rejects publishing an event without a description', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create(['description' => null]);

    $this->actingAs($admin)
        ->patch("/events/{$event->id}/publish")
        ->assertSessionHasErrors(['description']);

    expect($event->fresh()->status)->toBe(EventStatus::Draft);
});

it('rejects publishing an event without a venue', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->withoutVenue()->create();

    $this->actingAs($admin)
        ->patch("/events/{$event->id}/publish")
        ->assertSessionHasErrors(['venue_id']);

    expect($event->fresh()->status)->toBe(EventStatus::Draft);
});

it('allows admins to unpublish a published event', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->published()->create();

    $this->actingAs($admin)
        ->patch("/events/{$event->id}/unpublish")
        ->assertRedirect();

    expect($event->fresh()->status)->toBe(EventStatus::Draft);
});

it('allows re-publishing after unpublishing', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->published()->create();

    $this->actingAs($admin)
        ->patch("/events/{$event->id}/unpublish");

    expect($event->fresh()->status)->toBe(EventStatus::Draft);

    $this->actingAs($admin)
        ->patch("/events/{$event->id}/publish")
        ->assertRedirect();

    expect($event->fresh()->status)->toBe(EventStatus::Published);
});

it('forbids non-admin users from publishing', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create();

    $this->actingAs($user)
        ->patch("/events/{$event->id}/publish")
        ->assertForbidden();
});

it('forbids non-admin users from unpublishing', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->published()->create();

    $this->actingAs($user)
        ->patch("/events/{$event->id}/unpublish")
        ->assertForbidden();
});
