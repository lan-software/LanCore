<?php

use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Security\Exceptions\ExpiredTokenException;
use App\Domain\Ticketing\Security\Exceptions\InvalidSignatureException;
use App\Domain\Ticketing\Security\Exceptions\UnknownKidException;
use App\Domain\Ticketing\Security\TicketKeyRing;
use App\Domain\Ticketing\Security\TicketTokenService;

beforeEach(function (): void {
    setUpTicketSigningKey('kid20260101a');
    $this->service = new TicketTokenService(new TicketKeyRing);
});

it('round-trips issue and verify', function (): void {
    $ticket = Ticket::factory()->create();

    $issued = $this->service->issue($ticket);
    $verification = $this->service->verify($issued->qrPayload);

    expect($verification->tid)->toBe($ticket->id);
    expect($verification->kid)->toBe('kid20260101a');
});

it('rejects tampered body', function (): void {
    $ticket = Ticket::factory()->create();
    $issued = $this->service->issue($ticket);

    [$ver, $kid, $body, $sig] = explode('.', $issued->qrPayload);
    $tampered = "{$ver}.{$kid}.".TicketKeyRing::base64UrlEncode('{"tid":99999,"nonce":"x","iat":0,"exp":99999999999}').".{$sig}";

    expect(fn () => $this->service->verify($tampered))->toThrow(InvalidSignatureException::class);
});

it('rejects tampered signature', function (): void {
    $ticket = Ticket::factory()->create();
    $issued = $this->service->issue($ticket);
    $tampered = $issued->qrPayload.'AA';

    expect(fn () => $this->service->verify($tampered))->toThrow(InvalidSignatureException::class);
});

it('rejects unknown kid', function (): void {
    $ticket = Ticket::factory()->create();
    $issued = $this->service->issue($ticket);

    [$ver, , $body, $sig] = explode('.', $issued->qrPayload);
    $forged = "{$ver}.unknownkid.{$body}.{$sig}";

    expect(fn () => $this->service->verify($forged))->toThrow(UnknownKidException::class);
});

it('rejects expired tokens', function (): void {
    $ticket = Ticket::factory()->create();
    $issued = $this->service->issue($ticket);

    [$ver, $kid, $bodyB64] = explode('.', $issued->qrPayload);
    $body = json_decode(TicketKeyRing::base64UrlDecode($bodyB64), true);
    $body['exp'] = time() - 10;
    $newBodyB64 = TicketKeyRing::base64UrlEncode(json_encode($body));
    $ring = new TicketKeyRing;
    $sig = TicketKeyRing::base64UrlEncode($ring->sign($kid, "{$ver}.{$kid}.{$newBodyB64}"));

    expect(fn () => $this->service->verify("{$ver}.{$kid}.{$newBodyB64}.{$sig}"))->toThrow(ExpiredTokenException::class);
});

it('locates ticket by nonce hash', function (): void {
    $ticket = Ticket::factory()->create();
    $payload = $ticket->issueSignedToken($this->service);
    $verification = $this->service->verify($payload);

    $located = $this->service->locate($verification);
    expect($located)->not->toBeNull();
    expect($located->id)->toBe($ticket->id);
});

it('returns null when nonce hash no longer matches', function (): void {
    $ticket = Ticket::factory()->create();
    $payload = $ticket->issueSignedToken($this->service);
    $verification = $this->service->verify($payload);

    // rotate token; nonce_hash now differs
    $ticket->issueSignedToken($this->service);

    expect($this->service->locate($verification))->toBeNull();
});

it('produces deterministic nonce hash for same pepper and divergent for different', function (): void {
    $nonce = random_bytes(16);
    $a = hash_hmac('sha256', $nonce, 'pepper-1');
    $b = hash_hmac('sha256', $nonce, 'pepper-1');
    $c = hash_hmac('sha256', $nonce, 'pepper-2');

    expect($a)->toBe($b);
    expect($a)->not->toBe($c);
});
