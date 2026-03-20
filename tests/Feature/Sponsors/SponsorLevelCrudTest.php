<?php

use App\Domain\Sponsoring\Models\SponsorLevel;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows admins to view sponsor levels index', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/sponsor-levels')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('sponsor-levels/Index')
                ->has('sponsorLevels')
        );
});

it('allows admins to view the create page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/sponsor-levels/create')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page->component('sponsor-levels/Create')
        );
});

it('allows admins to store a sponsor level', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/sponsor-levels', [
            'name' => 'Platinum',
            'color' => '#E5E4E2',
        ])
        ->assertRedirect('/sponsor-levels');

    expect(SponsorLevel::where('name', 'Platinum')->exists())->toBeTrue();
});

it('auto-increments sort order on store', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    SponsorLevel::factory()->create(['sort_order' => 5]);

    $this->actingAs($admin)
        ->post('/sponsor-levels', [
            'name' => 'New Level',
            'color' => '#000000',
        ])
        ->assertRedirect('/sponsor-levels');

    expect(SponsorLevel::where('name', 'New Level')->first()->sort_order)->toBe(6);
});

it('validates required fields when storing', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/sponsor-levels', [])
        ->assertSessionHasErrors(['name', 'color']);
});

it('validates color format', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/sponsor-levels', [
            'name' => 'Bad Color',
            'color' => 'not-a-color',
        ])
        ->assertSessionHasErrors(['color']);
});

it('allows admins to view the edit page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $level = SponsorLevel::factory()->create();

    $this->actingAs($admin)
        ->get("/sponsor-levels/{$level->id}")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('sponsor-levels/Edit')
                ->has('sponsorLevel')
                ->where('sponsorLevel.id', $level->id)
        );
});

it('allows admins to update a sponsor level', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $level = SponsorLevel::factory()->create();

    $this->actingAs($admin)
        ->patch("/sponsor-levels/{$level->id}", [
            'name' => 'Diamond',
            'color' => '#B9F2FF',
        ])
        ->assertRedirect();

    expect($level->fresh())
        ->name->toBe('Diamond')
        ->color->toBe('#B9F2FF');
});

it('allows admins to delete a sponsor level', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $level = SponsorLevel::factory()->create();
    $levelId = $level->id;

    $this->actingAs($admin)
        ->delete("/sponsor-levels/{$levelId}")
        ->assertRedirect('/sponsor-levels');

    expect(SponsorLevel::find($levelId))->toBeNull();
});
