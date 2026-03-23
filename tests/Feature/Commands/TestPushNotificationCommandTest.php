<?php

use App\Domain\Notification\Models\PushSubscription;
use App\Models\User;

beforeEach(function () {
    config([
        'services.vapid.subject' => 'mailto:test@example.com',
        'services.vapid.public_key' => 'BEeO8XiI4TLfLoOWhhoXcTaE9-UEs7xLQgnhZe4mRInJsq5YctH6i7tS0vRADBntXctFcbUMer1pDiPPAAQh4cU',
        'services.vapid.private_key' => 'XfxetPqOW_c82FANZV5GaiAvx0kdYDrnjJsqqPVHBFs',
    ]);
});

function createSubscription(User $user, string $endpoint): PushSubscription
{
    return PushSubscription::create([
        'user_id' => $user->id,
        'endpoint' => $endpoint,
        'public_key' => 'BOrNEcTwsyy6nb11MfQPIXuhZwzL0Ib1Y0wFs7VF4GihqN5Nqdx9wnS_eJG6aLYzs4skhFaViKZAhYnrcHH9Jl8',
        'auth_token' => 'hLPftB1xPM_ApQnYWzeUew',
        'content_encoding' => 'aesgcm',
    ]);
}

it('fails when no push subscription exists', function () {
    $this->artisan('test:notification:push')
        ->expectsOutputToContain('No push subscription found')
        ->assertFailed();
});

it('fails when user is not found by id', function () {
    $this->artisan('test:notification:push --user=999')
        ->expectsOutputToContain("User '999' not found")
        ->assertFailed();
});

it('fails when user is not found by email', function () {
    $this->artisan('test:notification:push --user=missing@example.com')
        ->expectsOutputToContain("User 'missing@example.com' not found")
        ->assertFailed();
});

it('fails when user found by id has no push subscription', function () {
    $user = User::factory()->create();

    $this->artisan("test:notification:push --user={$user->id}")
        ->expectsOutputToContain('No push subscription found')
        ->assertFailed();
});

it('fails when user found by email has no push subscription', function () {
    $user = User::factory()->create(['email' => 'no-sub@example.com']);

    $this->artisan('test:notification:push --user=no-sub@example.com')
        ->expectsOutputToContain('No push subscription found')
        ->assertFailed();
});

it('resolves subscription for user by numeric id', function () {
    $user = User::factory()->create();
    createSubscription($user, 'https://push.example.com/user-by-id');

    $this->artisan("test:notification:push --user={$user->id}")
        ->expectsOutputToContain('Sending push to:')
        ->expectsOutputToContain('push.example.com/user-by-id');
});

it('resolves subscription for user by email', function () {
    $user = User::factory()->create(['email' => 'push@example.com']);
    createSubscription($user, 'https://push.example.com/user-by-email');

    $this->artisan('test:notification:push --user=push@example.com')
        ->expectsOutputToContain('Sending push to:')
        ->expectsOutputToContain('push.example.com/user-by-email');
});

it('resolves latest subscription when no user specified', function () {
    $user = User::factory()->create();
    createSubscription($user, 'https://push.example.com/default-sub');

    $this->artisan('test:notification:push')
        ->expectsOutputToContain('Sending push to:')
        ->expectsOutputToContain('push.example.com/default-sub');
});

it('resolves latest subscription for user with multiple subscriptions', function () {
    $user = User::factory()->create();

    $old = createSubscription($user, 'https://push.example.com/old-endpoint');
    $old->update(['created_at' => now()->subDay(), 'updated_at' => now()->subDay()]);

    createSubscription($user, 'https://push.example.com/latest-endpoint');

    $this->artisan("test:notification:push --user={$user->id}")
        ->expectsOutputToContain('push.example.com/latest-endpoint');
});

it('reports failure when push notification fails', function () {
    $user = User::factory()->create();
    createSubscription($user, 'https://push.example.com/fail-endpoint');

    $this->artisan('test:notification:push')
        ->expectsOutputToContain('Failed to send push notification:')
        ->assertFailed();
});
