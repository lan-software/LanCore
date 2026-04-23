<?php

use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Security\TicketKeyRing;
use App\Domain\Ticketing\Security\TicketTokenService;

beforeEach(function (): void {
    setUpTicketSigningKey('kid20260101a');
    $this->service = new TicketTokenService(new TicketKeyRing);
});

it('rotate mints a token and records an epoch', function (): void {
    $ticket = Ticket::factory()->create();

    $payload = $ticket->rotateSignedToken($this->service);
    $ticket->refresh();

    expect($payload)->toStartWith('LCT1.');
    expect($ticket->validation_rotation_epoch)->toBe(1);
    expect($ticket->validation_nonce_hash)->not->toBeNull();
});

it('render returns the same payload on repeated calls without touching the nonce hash', function (): void {
    $ticket = Ticket::factory()->create();
    $ticket->rotateSignedToken($this->service);
    $ticket->refresh();

    $first = $ticket->renderSignedToken($this->service);
    $hashBefore = $ticket->validation_nonce_hash;
    $epochBefore = $ticket->validation_rotation_epoch;

    $second = $ticket->renderSignedToken($this->service);
    $ticket->refresh();

    expect($second)->toBe($first);
    expect($ticket->validation_nonce_hash)->toBe($hashBefore);
    expect($ticket->validation_rotation_epoch)->toBe($epochBefore);
});

it('rotate bumps the epoch and produces a different payload', function (): void {
    $ticket = Ticket::factory()->create();

    $first = $ticket->rotateSignedToken($this->service);
    $ticket->refresh();
    $epochA = $ticket->validation_rotation_epoch;

    $second = $ticket->rotateSignedToken($this->service);
    $ticket->refresh();

    expect($ticket->validation_rotation_epoch)->toBe($epochA + 1);
    expect($second)->not->toBe($first);
});

it('render throws when no token has ever been issued', function (): void {
    $ticket = Ticket::factory()->create([
        'validation_kid' => null,
        'validation_issued_at' => null,
        'validation_expires_at' => null,
    ]);

    expect(fn () => $ticket->renderSignedToken($this->service))
        ->toThrow(RuntimeException::class);
});
