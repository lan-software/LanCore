<?php

use App\Domain\Theme\Models\Theme;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
});

it('forbids a regular user from listing themes', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->get('/themes')
        ->assertForbidden();
});

it('forbids a regular user from creating a theme', function () {
    $user = User::factory()->withRole(RoleName::User)->create();

    $this->actingAs($user)
        ->post('/themes', [
            'name' => 'Sneaky',
        ])
        ->assertForbidden();
});

it('forbids a regular user from updating a theme', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $theme = Theme::factory()->withPalette()->create();

    $this->actingAs($user)
        ->patch("/themes/{$theme->id}", [
            'name' => 'Changed',
        ])
        ->assertForbidden();
});

it('forbids a regular user from deleting a theme', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $theme = Theme::factory()->withPalette()->create();

    $this->actingAs($user)
        ->delete("/themes/{$theme->id}")
        ->assertForbidden();

    expect(Theme::find($theme->id))->not->toBeNull();
});

it('forbids a regular user from setting the site-wide default', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $theme = Theme::factory()->withPalette()->create();

    $this->actingAs($user)
        ->patch('/themes/default', ['theme_id' => $theme->id])
        ->assertForbidden();
});
