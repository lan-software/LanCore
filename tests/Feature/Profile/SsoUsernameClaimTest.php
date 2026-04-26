<?php

use App\Domain\Integration\Actions\ResolveIntegrationUser;
use App\Domain\Integration\Models\IntegrationApp;
use App\Domain\Profile\Enums\AvatarSource;
use App\Models\User;

/**
 * @see docs/mil-std-498/STD.md §4.27 Username SSO Claim Tests
 * @see docs/mil-std-498/SRS.md ICLIB-F-002 (amended), ICLIB-F-010
 */
function makeApp(array $scopes = ['user:read', 'user:email', 'user:roles']): IntegrationApp
{
    $app = new IntegrationApp;
    $app->allowed_scopes = $scopes;

    return $app;
}

test('payload includes username when set', function () {
    $user = User::factory()->create(['username' => 'neo_42']);

    $payload = app(ResolveIntegrationUser::class)->execute($user, makeApp());

    expect($payload['username'])->toBe('neo_42');
});

test('payload returns null username for users still in transitional state', function () {
    $user = User::factory()->withoutUsername()->create();

    $payload = app(ResolveIntegrationUser::class)->execute($user, makeApp());

    expect($payload['username'])->toBeNull();
});

test('payload always carries a non-null avatar_url', function () {
    $user = User::factory()->create(['avatar_source' => AvatarSource::Default]);

    $payload = app(ResolveIntegrationUser::class)->execute($user, makeApp());

    expect($payload['avatar_url'])->toBeString()->not->toBe('');
});

test('payload includes profile_url when username is set', function () {
    $user = User::factory()->create(['username' => 'neo_42']);

    $payload = app(ResolveIntegrationUser::class)->execute($user, makeApp());

    expect($payload['profile_url'])->toContain('/u/neo_42');
});

test('profile_url is null when username is not set', function () {
    $user = User::factory()->withoutUsername()->create();

    $payload = app(ResolveIntegrationUser::class)->execute($user, makeApp());

    expect($payload['profile_url'])->toBeNull();
});
