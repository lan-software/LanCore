<?php

use App\Domain\Ticketing\Security\Exceptions\UnknownKidException;
use App\Domain\Ticketing\Security\TicketKeyRing;

beforeEach(function (): void {
    setUpTicketSigningKey('kid20260101a');
});

it('signs and verifies messages with the active kid', function (): void {
    $ring = new TicketKeyRing;
    $sig = $ring->sign('kid20260101a', 'hello world');

    expect(sodium_crypto_sign_verify_detached($sig, 'hello world', $ring->publicKey('kid20260101a')))->toBeTrue();
});

it('throws on unknown kid for sign and publicKey', function (): void {
    $ring = new TicketKeyRing;

    expect(fn () => $ring->sign('nope', 'x'))->toThrow(UnknownKidException::class);
    expect(fn () => $ring->publicKey('nope'))->toThrow(UnknownKidException::class);
});

it('exposes all verify kids and JWKS shape', function (): void {
    $oldKid = 'kid20260101a';
    $newKid = 'kid20260201b';

    $dir = (string) config('tickets.signing.keys_path');
    file_put_contents($dir.'/'.$newKid.'.key', sodium_crypto_sign_keypair());
    config()->set('tickets.signing.active_kid', $newKid);
    config()->set('tickets.signing.verify_kids', [$oldKid, $newKid]);

    $ring = new TicketKeyRing;

    expect($ring->allVerifyKids())->toContain($oldKid)->toContain($newKid);

    $jwks = $ring->toJwks();
    expect($jwks)->toHaveKey('keys');
    expect($jwks['keys'])->toHaveCount(2);
    expect($jwks['keys'][0])->toHaveKeys(['kid', 'kty', 'crv', 'x']);
    expect($jwks['keys'][0]['kty'])->toBe('OKP');
    expect($jwks['keys'][0]['crv'])->toBe('Ed25519');
});

it('returns the configured active kid', function (): void {
    expect((new TicketKeyRing)->activeKid())->toBe('kid20260101a');
});
