<?php

/**
 * @see docs/mil-std-498/SSS.md CAP-NTF-002
 * @see docs/mil-std-498/SRS.md NTF-F-003
 */

use App\Domain\Notification\Models\PushSubscription;
use App\Models\User;

it('stores a new push subscription', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/push-subscriptions', [
            'endpoint' => 'https://push.example.com/subscription/abc123',
            'public_key' => 'BNcRdreALRFXTkOOUHK1EtK2wtaz5Ry4YfYCA_0QTpQtUbVlUls0VJXg7A8u-Ts1XbjhazAkj7I99e8p8jfR6A',
            'auth_token' => 'tBHItJI5svbpC7VsjrxcZQ',
        ])
        ->assertSuccessful()
        ->assertJson(['subscribed' => true]);

    expect(PushSubscription::where('user_id', $user->id)->count())->toBe(1);

    $subscription = PushSubscription::where('user_id', $user->id)->first();
    expect($subscription->endpoint)->toBe('https://push.example.com/subscription/abc123')
        ->and($subscription->public_key)->toBe('BNcRdreALRFXTkOOUHK1EtK2wtaz5Ry4YfYCA_0QTpQtUbVlUls0VJXg7A8u-Ts1XbjhazAkj7I99e8p8jfR6A')
        ->and($subscription->auth_token)->toBe('tBHItJI5svbpC7VsjrxcZQ')
        ->and($subscription->content_encoding)->toBe('aesgcm');
});

it('replaces an existing subscription for the same endpoint', function () {
    $user = User::factory()->create();

    PushSubscription::create([
        'user_id' => $user->id,
        'endpoint' => 'https://push.example.com/subscription/abc123',
        'public_key' => 'old-key',
        'auth_token' => 'old-token',
        'content_encoding' => 'aesgcm',
    ]);

    $this->actingAs($user)
        ->postJson('/push-subscriptions', [
            'endpoint' => 'https://push.example.com/subscription/abc123',
            'public_key' => 'new-key',
            'auth_token' => 'new-token',
        ])
        ->assertSuccessful();

    expect(PushSubscription::where('user_id', $user->id)->count())->toBe(1);

    $subscription = PushSubscription::where('user_id', $user->id)->first();
    expect($subscription->public_key)->toBe('new-key')
        ->and($subscription->auth_token)->toBe('new-token');
});

it('supports custom content encoding', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/push-subscriptions', [
            'endpoint' => 'https://push.example.com/subscription/xyz',
            'public_key' => 'some-key',
            'auth_token' => 'some-token',
            'content_encoding' => 'aes128gcm',
        ])
        ->assertSuccessful();

    $subscription = PushSubscription::where('user_id', $user->id)->first();
    expect($subscription->content_encoding)->toBe('aes128gcm');
});

it('removes a push subscription by endpoint', function () {
    $user = User::factory()->create();

    PushSubscription::create([
        'user_id' => $user->id,
        'endpoint' => 'https://push.example.com/subscription/abc123',
        'public_key' => 'key',
        'auth_token' => 'token',
        'content_encoding' => 'aesgcm',
    ]);

    $this->actingAs($user)
        ->deleteJson('/push-subscriptions', [
            'endpoint' => 'https://push.example.com/subscription/abc123',
        ])
        ->assertSuccessful()
        ->assertJson(['subscribed' => false]);

    expect(PushSubscription::where('user_id', $user->id)->count())->toBe(0);
});

it('does not remove other users subscriptions', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $endpoint = 'https://push.example.com/subscription/shared-endpoint';

    PushSubscription::create([
        'user_id' => $user->id,
        'endpoint' => $endpoint,
        'public_key' => 'key-a',
        'auth_token' => 'token-a',
        'content_encoding' => 'aesgcm',
    ]);

    PushSubscription::create([
        'user_id' => $otherUser->id,
        'endpoint' => $endpoint,
        'public_key' => 'key-b',
        'auth_token' => 'token-b',
        'content_encoding' => 'aesgcm',
    ]);

    $this->actingAs($user)
        ->deleteJson('/push-subscriptions', ['endpoint' => $endpoint])
        ->assertSuccessful();

    expect(PushSubscription::where('user_id', $user->id)->count())->toBe(0)
        ->and(PushSubscription::where('user_id', $otherUser->id)->count())->toBe(1);
});

it('requires authentication to store a push subscription', function () {
    $this->postJson('/push-subscriptions', [
        'endpoint' => 'https://push.example.com/subscription/abc123',
        'public_key' => 'key',
        'auth_token' => 'token',
    ])->assertUnauthorized();
});

it('validates the endpoint is a valid URL', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/push-subscriptions', [
            'endpoint' => 'not-a-url',
            'public_key' => 'key',
            'auth_token' => 'token',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('endpoint');
});

it('validates required fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/push-subscriptions', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['endpoint', 'public_key', 'auth_token']);
});

it('rejects invalid content encoding', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/push-subscriptions', [
            'endpoint' => 'https://push.example.com/subscription/abc',
            'public_key' => 'key',
            'auth_token' => 'token',
            'content_encoding' => 'invalid-encoding',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('content_encoding');
});

it('allows a user to have multiple subscriptions for different endpoints', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/push-subscriptions', [
            'endpoint' => 'https://push.example.com/subscription/device-1',
            'public_key' => 'key-1',
            'auth_token' => 'token-1',
        ])
        ->assertSuccessful();

    $this->actingAs($user)
        ->postJson('/push-subscriptions', [
            'endpoint' => 'https://push.example.com/subscription/device-2',
            'public_key' => 'key-2',
            'auth_token' => 'token-2',
        ])
        ->assertSuccessful();

    expect(PushSubscription::where('user_id', $user->id)->count())->toBe(2);
});
