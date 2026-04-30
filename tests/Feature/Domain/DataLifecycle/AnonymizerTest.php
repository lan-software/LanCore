<?php

use App\Domain\DataLifecycle\Anonymizers\PolicyAnonymizer;
use App\Domain\DataLifecycle\Anonymizers\SessionsAnonymizer;
use App\Domain\DataLifecycle\Anonymizers\UserAnonymizer;
use App\Domain\DataLifecycle\Enums\AnonymizationMode;
use App\Models\User;
use Database\Seeders\RetentionPolicySeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->seed(RetentionPolicySeeder::class);
});

it('UserAnonymizer scrubs every PII column but keeps email_hash', function () {
    $user = User::factory()->withCompleteProfile()->create([
        'email' => 'pii@example.com',
        'username' => 'piiuser',
        'short_bio' => 'I love LAN parties.',
    ]);
    $originalHash = $user->email_hash;

    app(UserAnonymizer::class)->anonymize($user, AnonymizationMode::Anonymize);

    $user->refresh();
    expect($user->email)->toBe("deleted-{$user->id}@anonymized.invalid");
    expect($user->username)->toBe("deleted_{$user->id}");
    expect($user->name)->toStartWith('Deleted User #');
    expect($user->phone)->toBeNull();
    expect($user->street)->toBeNull();
    expect($user->short_bio)->toBeNull();
    expect($user->two_factor_secret)->toBeNull();
    expect($user->remember_token)->toBeNull();
    expect($user->email_hash)->toBe($originalHash);
    expect($user->isAnonymized())->toBeTrue();
});

it('SessionsAnonymizer hard-deletes the user sessions', function () {
    if (! Schema::hasTable('sessions')) {
        $this->markTestSkipped('sessions table not present in this test config.');
    }

    $user = User::factory()->create();
    DB::table('sessions')->insert([
        'id' => 'test-session-id-'.$user->id,
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'TestAgent/1.0',
        'payload' => base64_encode('payload'),
        'last_activity' => time(),
    ]);

    $result = app(SessionsAnonymizer::class)->anonymize($user, AnonymizationMode::Anonymize);

    expect($result->recordsScrubbed)->toBeGreaterThan(0);
    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);
});

it('PolicyAnonymizer is a no-op when the user has no acceptances', function () {
    $user = User::factory()->create();

    $result = app(PolicyAnonymizer::class)->anonymize($user, AnonymizationMode::Anonymize);

    expect($result->recordsScrubbed)->toBe(0);
});
