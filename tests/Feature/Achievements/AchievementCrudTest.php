<?php

use App\Domain\Achievements\Enums\GrantableEvent;
use App\Domain\Achievements\Models\Achievement;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows admins to view the achievements index', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/achievements-admin')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('achievements/Index'));
});

it('prevents regular users from viewing the achievements index', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/achievements-admin')
        ->assertForbidden();
});

it('allows admins to view the create achievement page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/achievements-admin/create')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('achievements/Create'));
});

it('allows admins to store a new achievement', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/achievements-admin', [
            'name' => 'First Login',
            'description' => 'Awarded for your first login.',
            'notification_text' => 'Congratulations on your first login!',
            'color' => '#22c55e',
            'icon' => 'star',
            'is_active' => true,
            'event_classes' => [GrantableEvent::UserRegistered->value],
        ])
        ->assertRedirect('/achievements-admin');

    expect(Achievement::where('name', 'First Login')->exists())->toBeTrue();

    $achievement = Achievement::where('name', 'First Login')->first();
    expect($achievement->achievementEvents)->toHaveCount(1);
    expect($achievement->achievementEvents->first()->event_class)->toBe(GrantableEvent::UserRegistered->value);
});

it('validates required fields when storing an achievement', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/achievements-admin', [])
        ->assertSessionHasErrors(['name', 'color', 'icon']);
});

it('allows admins to view the edit achievement page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $achievement = Achievement::factory()->create();

    $this->actingAs($admin)
        ->get("/achievements-admin/{$achievement->id}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('achievements/Edit'));
});

it('allows admins to update an achievement', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $achievement = Achievement::factory()->create(['name' => 'Old Name']);

    $this->actingAs($admin)
        ->patch("/achievements-admin/{$achievement->id}", [
            'name' => 'Updated Name',
            'description' => 'Updated description.',
            'color' => '#ef4444',
            'icon' => 'medal',
            'is_active' => true,
            'event_classes' => [GrantableEvent::AnnouncementPublished->value],
        ])
        ->assertRedirect();

    $achievement->refresh();
    expect($achievement->name)->toBe('Updated Name');
    expect($achievement->color)->toBe('#ef4444');
    expect($achievement->achievementEvents)->toHaveCount(1);
});

it('allows admins to delete an achievement', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $achievement = Achievement::factory()->create();

    $this->actingAs($admin)
        ->delete("/achievements-admin/{$achievement->id}")
        ->assertRedirect('/achievements-admin');

    expect(Achievement::find($achievement->id))->toBeNull();
});

it('validates color format', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/achievements-admin', [
            'name' => 'Test',
            'color' => 'invalid',
            'icon' => 'star',
        ])
        ->assertSessionHasErrors('color');
});
