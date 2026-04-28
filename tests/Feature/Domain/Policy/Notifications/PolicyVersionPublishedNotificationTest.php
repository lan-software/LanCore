<?php

use App\Domain\Policy\Models\Policy;
use App\Domain\Policy\Models\PolicyVersion;
use App\Domain\Policy\Notifications\PolicyVersionPublishedNotification;
use App\Models\User;
use App\Support\StorageRole;

it('attaches the stored PDF and renders the public_statement inline', function (): void {
    StorageRole::private()->put('policy-versions/test.pdf', '%PDF-1.4 fake');

    $policy = Policy::factory()->create(['name' => 'Privacy Policy']);
    $version = PolicyVersion::factory()->for($policy)->create([
        'pdf_path' => 'policy-versions/test.pdf',
        'public_statement' => 'We changed our data retention period.',
        'is_non_editorial_change' => true,
    ]);

    $user = User::factory()->create(['locale' => 'de']);

    $mail = (new PolicyVersionPublishedNotification($version))->toMail($user);

    expect($mail->subject)->toContain('Privacy Policy');

    $allLines = array_merge(
        is_array($mail->introLines) ? $mail->introLines : [],
        is_array($mail->outroLines) ? $mail->outroLines : [],
    );
    $rendered = implode("\n", array_map(fn ($l) => is_string($l) ? $l : '', $allLines));
    expect($rendered)->toContain('We changed our data retention period.');

    $attachments = $mail->rawAttachments;
    expect($attachments)->not->toBeEmpty();
});

it('locale matches the recipient locale', function (): void {
    $policy = Policy::factory()->create();
    $version = PolicyVersion::factory()->for($policy)->create();

    $user = User::factory()->create(['locale' => 'de']);

    $mail = (new PolicyVersionPublishedNotification($version))->toMail($user);

    expect($mail->locale)->toBe('de');
});
