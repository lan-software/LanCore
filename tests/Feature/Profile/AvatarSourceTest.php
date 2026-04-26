<?php

use App\Domain\Profile\Enums\AvatarSource;
use App\Models\User;

/**
 * @see docs/mil-std-498/STD.md §4.25 Profile Customization Tests (avatar source)
 * @see docs/mil-std-498/SRS.md USR-F-024
 */
test('default avatar source resolves to a non-null URL', function () {
    $user = User::factory()->create([
        'avatar_source' => AvatarSource::Default,
        'avatar_path' => null,
    ]);

    expect($user->avatarUrl())->toBeString()->not->toBe('');
});

test('gravatar source includes the email md5', function () {
    $user = User::factory()->create([
        'avatar_source' => AvatarSource::Gravatar,
        'email' => 'm@example.com',
    ]);

    $expected = md5('m@example.com');
    expect($user->avatarUrl())->toContain($expected)->toContain('gravatar.com');
});

test('steam source falls back to default until that iteration ships', function () {
    $defaultUser = User::factory()->create(['avatar_source' => AvatarSource::Default]);
    $steamUser = User::factory()->create(['avatar_source' => AvatarSource::Steam]);

    expect($steamUser->avatarUrl())->toBe($defaultUser->avatarUrl());
});

test('custom source without an avatar_path falls back to default', function () {
    $defaultUser = User::factory()->create(['avatar_source' => AvatarSource::Default]);
    $customUser = User::factory()->create([
        'avatar_source' => AvatarSource::Custom,
        'avatar_path' => null,
    ]);

    expect($customUser->avatarUrl())->toBe($defaultUser->avatarUrl());
});
