<?php

use App\Domain\Integration\Actions\ResolveIntegrationUser;
use App\Domain\Integration\Models\IntegrationApp;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md I18N-F-007
 *
 * Regression guard: the SSO payload must return the user's stored locale,
 * not the request-scoped application locale. The old implementation called
 * `app()->getLocale()`, which leaked the caller's request locale to every
 * consumer and broke per-user locale propagation across Lan* apps.
 */
test('resolves locale from the user record, not the request locale', function () {
    app()->setLocale('de');

    $user = User::factory()->create(['locale' => 'fr']);
    $app = IntegrationApp::factory()->create([
        'allowed_scopes' => ['user:read'],
    ]);

    $payload = app(ResolveIntegrationUser::class)->execute($user, $app);

    expect($payload['locale'] ?? null)->toBe('fr');
});

test('falls back to the app fallback locale when the user has none', function () {
    app()->setLocale('de');

    $user = User::factory()->create(['locale' => null]);
    $app = IntegrationApp::factory()->create([
        'allowed_scopes' => ['user:read'],
    ]);

    $payload = app(ResolveIntegrationUser::class)->execute($user, $app);

    expect($payload['locale'] ?? null)->toBe(config('app.fallback_locale'));
});
