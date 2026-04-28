<?php

use App\Domain\Policy\Gdpr\GdprExportContext;
use App\Models\User;

it('assigns user_a, user_b in deterministic encounter order', function (): void {
    $subject = new User;
    $subject->id = 1;
    $alice = new User;
    $alice->id = 2;
    $bob = new User;
    $bob->id = 3;

    $ctx = new GdprExportContext($subject, new DateTimeImmutable);

    expect($ctx->obfuscateUser($alice))->toBe('user_a')
        ->and($ctx->obfuscateUser($bob))->toBe('user_b')
        ->and($ctx->obfuscateUser($alice))->toBe('user_a')
        ->and($ctx->obfuscateUser($subject))->toBe('subject');
});

it('manifest has no reverse mapping (only pseudonym → hint)', function (): void {
    $subject = new User;
    $subject->id = 1;
    $other = new User;
    $other->id = 99;

    $ctx = new GdprExportContext($subject, new DateTimeImmutable);
    $ctx->obfuscateUser($other, 'team mate');

    $table = $ctx->pseudonymTable();

    expect($table)->toHaveKey('user_a')
        ->and($table['user_a'])->toBe('team mate');

    foreach ($table as $key => $value) {
        expect($value)->not->toContain('99');
    }
});

it('returns "subject" for the export subject and never pseudonymises them', function (): void {
    $subject = new User;
    $subject->id = 42;

    $ctx = new GdprExportContext($subject, new DateTimeImmutable);

    expect($ctx->obfuscateUser($subject))->toBe('subject')
        ->and($ctx->obfuscateUser(42))->toBe('subject');
});
