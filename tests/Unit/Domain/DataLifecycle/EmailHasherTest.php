<?php

use App\Domain\DataLifecycle\Services\EmailHasher;

it('produces a deterministic 64-char hex hash', function () {
    $hasher = app(EmailHasher::class);
    $hash = $hasher->hash('user@example.com');

    expect($hash)->toBeString()->toHaveLength(64);
    expect(ctype_xdigit($hash))->toBeTrue();
    expect($hasher->hash('user@example.com'))->toBe($hash);
});

it('normalizes case and surrounding whitespace before hashing', function () {
    $hasher = app(EmailHasher::class);

    expect($hasher->hash('User@Example.com'))
        ->toBe($hasher->hash('user@example.com'));

    expect($hasher->hash('  user@example.com  '))
        ->toBe($hasher->hash('user@example.com'));
});

it('produces different hashes for different addresses', function () {
    $hasher = app(EmailHasher::class);

    expect($hasher->hash('a@example.com'))
        ->not->toBe($hasher->hash('b@example.com'));
});

it('rotates with the app key context (regression guard)', function () {
    $hasher = app(EmailHasher::class);
    $original = $hasher->hash('user@example.com');

    config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
    app()->forgetInstance(EmailHasher::class);

    $rotated = app(EmailHasher::class)->hash('user@example.com');

    expect($rotated)->not->toBe($original);
});
