<?php

use App\Models\User;

test('set locale middleware applies the user locale', function () {
    $user = User::factory()->create(['locale' => 'de']);

    $this->actingAs($user)->get(route('profile.edit'))->assertOk();

    expect(app()->getLocale())->toBe('de');
});

test('set locale middleware falls back when user has no locale', function () {
    $user = User::factory()->create(['locale' => null]);

    $this->actingAs($user)->get(route('profile.edit'))->assertOk();

    expect(app()->getLocale())->toBe(config('app.fallback_locale'));
});

test('inertia response exposes locale and availableLocales in shared props', function () {
    $user = User::factory()->create(['locale' => 'fr']);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'fr')
            ->where('availableLocales', ['en', 'de', 'fr', 'es'])
            ->etc()
        );
});

test('profile update accepts a valid locale', function () {
    $user = User::factory()->create(['locale' => 'en']);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'locale' => 'es',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->locale)->toBe('es');
});

test('profile update rejects an unsupported locale', function () {
    $user = User::factory()->create(['locale' => 'en']);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'locale' => 'zz',
        ])
        ->assertSessionHasErrors('locale');

    expect($user->refresh()->locale)->toBe('en');
});
