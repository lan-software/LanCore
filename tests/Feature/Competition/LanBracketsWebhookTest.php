<?php

use App\Domain\Competition\Enums\CompetitionStatus;
use App\Domain\Competition\Models\Competition;

it('updates competition status on competition.completed webhook', function () {
    $competition = Competition::factory()->running()->syncedToLanBrackets()->create();

    $payload = json_encode([
        'event' => 'competition.completed',
        'timestamp' => now()->toISOString(),
        'data' => [
            'competition' => [
                'id' => $competition->lanbrackets_id,
                'external_reference_id' => (string) $competition->id,
            ],
        ],
    ]);

    $signature = hash_hmac('sha256', $payload, config('lanbrackets.webhook_secret', ''));

    $this->postJson('/webhooks/lanbrackets', json_decode($payload, true), [
        'X-LanBrackets-Signature' => $signature,
        'X-LanBrackets-Event' => 'competition.completed',
    ])
        ->assertOk();

    expect($competition->fresh()->status)->toBe(CompetitionStatus::Finished);
});

it('rejects webhook with invalid signature', function () {
    $payload = json_encode([
        'event' => 'competition.completed',
        'data' => [],
    ]);

    $this->postJson('/webhooks/lanbrackets', json_decode($payload, true), [
        'X-LanBrackets-Signature' => 'invalid-signature',
        'X-LanBrackets-Event' => 'competition.completed',
    ])
        ->assertForbidden();
});
