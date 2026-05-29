<?php

use App\Domain\Ticketing\Models\Ticket;

it('reports nothing to do when no tickets carry a nonce hash', function (): void {
    Ticket::factory()->create(['validation_nonce_hash' => null]);

    $this->artisan('tickets:rotate-all')
        ->expectsOutputToContain('No tickets require rotation.')
        ->assertSuccessful();
});

it('rotates every ticket that holds a nonce hash', function (): void {
    $ticket = Ticket::factory()->create([
        'validation_nonce_hash' => 'legacy-hash',
        'validation_rotation_epoch' => 0,
    ]);
    $other = Ticket::factory()->create([
        'validation_nonce_hash' => 'legacy-hash-2',
        'validation_rotation_epoch' => 3,
    ]);

    $this->artisan('tickets:rotate-all')
        ->expectsOutputToContain('Rotating 2 ticket(s)')
        ->expectsOutputToContain('Done. Rotated 2 ticket(s).')
        ->assertSuccessful();

    expect($ticket->fresh()->validation_rotation_epoch)->toBe(1)
        ->and($ticket->fresh()->validation_nonce_hash)->not->toBe('legacy-hash')
        ->and($other->fresh()->validation_rotation_epoch)->toBe(4);
});

it('limits rotation to legacy tickets with --only-legacy', function (): void {
    $legacy = Ticket::factory()->create([
        'validation_nonce_hash' => 'legacy-hash',
        'validation_rotation_epoch' => 0,
    ]);
    $alreadyRotated = Ticket::factory()->create([
        'validation_nonce_hash' => 'already-rotated',
        'validation_rotation_epoch' => 5,
    ]);

    $this->artisan('tickets:rotate-all --only-legacy')
        ->expectsOutputToContain('Rotating 1 ticket(s)')
        ->assertSuccessful();

    expect($legacy->fresh()->validation_rotation_epoch)->toBe(1)
        ->and($alreadyRotated->fresh()->validation_rotation_epoch)->toBe(5)
        ->and(trim((string) $alreadyRotated->fresh()->validation_nonce_hash))->toBe('already-rotated');
});
