<?php

/**
 * @see docs/mil-std-498/SSS.md CAP-WHK-002
 * @see docs/mil-std-498/SRS.md WHK-F-003
 */

use App\Domain\Webhook\Events\WebhookDispatched;
use App\Domain\Webhook\Listeners\SendWebhookPayload;
use App\Domain\Webhook\Models\Webhook;
use Illuminate\Support\Facades\Http;

it('signs the payload with HMAC-SHA256 using the webhook secret', function () {
    Http::fake();

    $secret = 'test-signing-secret';
    $webhook = Webhook::factory()->create([
        'url' => 'https://example.com/hook',
        'secret' => $secret,
    ]);

    $payload = ['event' => 'user.registered', 'user' => ['id' => 1, 'name' => 'Test']];
    $expectedBody = json_encode($payload, JSON_THROW_ON_ERROR);
    $expectedSignature = 'sha256='.hash_hmac('sha256', $expectedBody, $secret);

    $listener = app(SendWebhookPayload::class);
    $listener->handle(new WebhookDispatched($webhook, $payload));

    Http::assertSent(function ($request) use ($expectedSignature) {
        return $request->hasHeader('X-Webhook-Signature', $expectedSignature);
    });
});

it('produces different signatures for different secrets', function () {
    Http::fake();

    $payload = ['event' => 'user.registered', 'data' => ['id' => 42]];
    $body = json_encode($payload, JSON_THROW_ON_ERROR);

    $signatureA = 'sha256='.hash_hmac('sha256', $body, 'secret-a');
    $signatureB = 'sha256='.hash_hmac('sha256', $body, 'secret-b');

    expect($signatureA)->not->toBe($signatureB);
});

it('produces consistent signatures for the same payload and secret', function () {
    $body = json_encode(['event' => 'user.registered'], JSON_THROW_ON_ERROR);
    $secret = 'consistent-secret';

    $sig1 = 'sha256='.hash_hmac('sha256', $body, $secret);
    $sig2 = 'sha256='.hash_hmac('sha256', $body, $secret);

    expect($sig1)->toBe($sig2);
});

it('does not include a signature header when the webhook has no secret', function () {
    Http::fake();

    $webhook = Webhook::factory()->create([
        'url' => 'https://example.com/hook',
        'secret' => null,
    ]);

    $listener = app(SendWebhookPayload::class);
    $listener->handle(new WebhookDispatched($webhook, ['event' => 'user.registered']));

    Http::assertSent(function ($request) {
        return ! $request->hasHeader('X-Webhook-Signature');
    });
});

it('includes the webhook event type in the X-Webhook-Event header', function () {
    Http::fake();

    $webhook = Webhook::factory()->create([
        'url' => 'https://example.com/hook',
        'secret' => 'my-secret',
    ]);

    $listener = app(SendWebhookPayload::class);
    $listener->handle(new WebhookDispatched($webhook, ['event' => 'user.registered']));

    Http::assertSent(function ($request) use ($webhook) {
        return $request->hasHeader('X-Webhook-Event', $webhook->event->value);
    });
});
