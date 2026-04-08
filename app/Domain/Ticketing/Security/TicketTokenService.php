<?php

namespace App\Domain\Ticketing\Security;

use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Security\Exceptions\ExpiredTokenException;
use App\Domain\Ticketing\Security\Exceptions\InvalidSignatureException;
use App\Domain\Ticketing\Security\Exceptions\MalformedTokenException;
use App\Domain\Ticketing\Security\Exceptions\UnknownKidException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Issues and verifies LCT1 signed ticket tokens.
 *
 * @see docs/mil-std-498/SDD.md §3.3.2
 * @see docs/mil-std-498/IDD.md §3.11
 * @see docs/mil-std-498/SRS.md TKT-F-017..023
 */
class TicketTokenService
{
    public function __construct(private readonly TicketKeyRing $keyRing) {}

    public function issue(Ticket $ticket): IssuedToken
    {
        $kid = $this->keyRing->activeKid();
        $nonce = random_bytes(16);
        $nonceB64 = TicketKeyRing::base64UrlEncode($nonce);
        $nonceHash = hash_hmac('sha256', $nonce, $this->pepper());

        $issuedAt = Carbon::now();
        $expiresAt = $this->computeExpiry($ticket);

        $body = [
            'tid' => $ticket->id,
            'nonce' => $nonceB64,
            'iat' => $issuedAt->getTimestamp(),
            'exp' => $expiresAt->getTimestamp(),
            'evt' => $ticket->event_id,
        ];

        $bodyJson = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $bodyB64 = TicketKeyRing::base64UrlEncode($bodyJson);
        $version = (string) config('tickets.token.version', 'LCT1');
        $signingInput = "{$version}.{$kid}.{$bodyB64}";
        $signature = $this->keyRing->sign($kid, $signingInput);
        $sigB64 = TicketKeyRing::base64UrlEncode($signature);

        $payload = "{$version}.{$kid}.{$bodyB64}.{$sigB64}";

        return new IssuedToken(
            qrPayload: $payload,
            nonceHash: $nonceHash,
            kid: $kid,
            issuedAt: $issuedAt,
            expiresAt: $expiresAt,
        );
    }

    public function verify(string $payload): TokenVerification
    {
        $segments = explode('.', $payload);
        if (count($segments) !== 4) {
            throw new MalformedTokenException('Token must have 4 dot-separated segments.');
        }

        [$version, $kid, $bodyB64, $sigB64] = $segments;

        $expectedVersion = (string) config('tickets.token.version', 'LCT1');
        if ($version !== $expectedVersion) {
            throw new MalformedTokenException("Unsupported token version: {$version}");
        }

        if (! in_array($kid, $this->keyRing->allVerifyKids(), true)) {
            throw new UnknownKidException("Unknown kid: {$kid}");
        }

        $signature = TicketKeyRing::base64UrlDecode($sigB64);
        $signingInput = "{$version}.{$kid}.{$bodyB64}";
        $publicKey = $this->keyRing->publicKey($kid);

        if (strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES) {
            throw new InvalidSignatureException('Token signature has invalid length.');
        }

        try {
            $valid = sodium_crypto_sign_verify_detached($signature, $signingInput, $publicKey);
        } catch (\Throwable) {
            throw new InvalidSignatureException('Token signature verification failed.');
        }

        if (! $valid) {
            throw new InvalidSignatureException('Token signature verification failed.');
        }

        $bodyJson = TicketKeyRing::base64UrlDecode($bodyB64);
        try {
            /** @var array<string, mixed> $body */
            $body = json_decode($bodyJson, true, 8, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new MalformedTokenException('Token body is not valid JSON.');
        }

        foreach (['tid', 'nonce', 'iat', 'exp'] as $required) {
            if (! array_key_exists($required, $body)) {
                throw new MalformedTokenException("Token body missing required claim: {$required}");
            }
        }

        $exp = (int) $body['exp'];
        if ($exp < time()) {
            throw new ExpiredTokenException('Token has expired.');
        }

        return new TokenVerification(
            tid: (int) $body['tid'],
            nonce: (string) $body['nonce'],
            iat: (int) $body['iat'],
            exp: $exp,
            evt: isset($body['evt']) ? (int) $body['evt'] : null,
            kid: $kid,
        );
    }

    public function locate(TokenVerification $verification): ?Ticket
    {
        $nonce = TicketKeyRing::base64UrlDecode($verification->nonce);
        if ($nonce === '') {
            return null;
        }

        $hash = hash_hmac('sha256', $nonce, $this->pepper());

        return Ticket::query()
            ->where('id', $verification->tid)
            ->where('validation_nonce_hash', $hash)
            ->first();
    }

    private function pepper(): string
    {
        $pepper = (string) config('tickets.pepper');

        if ($pepper === '') {
            throw new \RuntimeException('TICKET_TOKEN_PEPPER is not configured.');
        }

        return $pepper;
    }

    private function computeExpiry(Ticket $ticket): Carbon
    {
        $graceHours = (int) config('tickets.token.grace_period_hours', 6);
        $eventEnd = $ticket->event?->end_date;

        if ($eventEnd instanceof Carbon) {
            return $eventEnd->copy()->addHours($graceHours);
        }

        Log::warning('Ticket event has no end_date; falling back to +1y token expiry.', [
            'ticket_id' => $ticket->id,
        ]);

        return Carbon::now()->addYear();
    }
}
