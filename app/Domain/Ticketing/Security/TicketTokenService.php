<?php

namespace App\Domain\Ticketing\Security;

use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Security\Exceptions\ExpiredTokenException;
use App\Domain\Ticketing\Security\Exceptions\InvalidSignatureException;
use App\Domain\Ticketing\Security\Exceptions\MalformedTokenException;
use App\Domain\Ticketing\Security\Exceptions\UnknownKidException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Issues and verifies LCT1 signed ticket tokens.
 *
 * Nonces are derived deterministically from HMAC(pepper, ticket_id || epoch).
 * The pepper lives outside the database, so a DB-only attacker cannot
 * reconstruct the nonce even though the epoch counter is stored alongside
 * the ticket row.
 *
 * Two mutation modes:
 *  - {@see rotate} increments `validation_rotation_epoch` and persists the
 *    resulting nonce hash + kid + issued/expires timestamps. This invalidates
 *    any previously-issued QR for the ticket.
 *  - {@see render} is a pure read: it recomputes the nonce from the stored
 *    epoch and re-signs against the stored kid/issued/expires fields.
 *    No DB writes.
 *
 * @see docs/mil-std-498/SDD.md §3.3.2
 * @see docs/mil-std-498/IDD.md §3.11
 * @see docs/mil-std-498/SRS.md TKT-F-017..027
 */
class TicketTokenService
{
    public function __construct(private readonly TicketKeyRing $keyRing) {}

    /**
     * Increment the rotation epoch and persist a new nonce hash + metadata.
     * Returns the freshly-signed token envelope for any caller that needs it.
     */
    public function rotate(Ticket $ticket): IssuedToken
    {
        return DB::transaction(function () use ($ticket): IssuedToken {
            $locked = Ticket::whereKey($ticket->getKey())->lockForUpdate()->first();
            if (! $locked) {
                throw new \RuntimeException("Ticket #{$ticket->getKey()} disappeared mid-rotation.");
            }

            $epoch = ((int) $locked->validation_rotation_epoch) + 1;
            $kid = $this->keyRing->activeKid();

            $issuedAt = Carbon::now();
            $expiresAt = $this->computeExpiry($locked);

            $envelope = $this->build($locked->id, $epoch, $kid, $issuedAt, $expiresAt);

            $locked->forceFill([
                'validation_nonce_hash' => $envelope->nonceHash,
                'validation_kid' => $kid,
                'validation_issued_at' => $issuedAt,
                'validation_expires_at' => $expiresAt,
                'validation_rotation_epoch' => $epoch,
            ])->save();

            // Sync the caller's instance so downstream code (PDF dispatch, etc.)
            // observes the new state without an explicit refresh.
            $ticket->setRawAttributes($locked->getAttributes(), true);

            return $envelope;
        });
    }

    /**
     * Render the currently-valid token without rotating the nonce.
     *
     * Reads the ticket's stored epoch, kid, issued/expires timestamps and
     * rebuilds the identical signed payload. Safe to call repeatedly.
     *
     * @throws \RuntimeException when the ticket has never been rotated
     *                           (i.e. no stored kid/issued/expires).
     */
    public function render(Ticket $ticket): string
    {
        if ($ticket->validation_kid === null
            || $ticket->validation_issued_at === null
            || $ticket->validation_expires_at === null
        ) {
            throw new \RuntimeException(
                "Ticket #{$ticket->getKey()} has no issued token; call rotate() first.",
            );
        }

        $epoch = (int) ($ticket->validation_rotation_epoch ?? 0);
        $issuedAt = $ticket->validation_issued_at instanceof Carbon
            ? $ticket->validation_issued_at
            : Carbon::parse($ticket->validation_issued_at);
        $expiresAt = $ticket->validation_expires_at instanceof Carbon
            ? $ticket->validation_expires_at
            : Carbon::parse($ticket->validation_expires_at);

        return $this->build(
            $ticket->id,
            $epoch,
            (string) $ticket->validation_kid,
            $issuedAt,
            $expiresAt,
        )->qrPayload;
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

    /**
     * Thin accessor so callers (e.g. legacy-rotate command) don't need to
     * reach into {@see TicketKeyRing} directly.
     */
    public function isVerifiable(string $kid): bool
    {
        return in_array($kid, $this->keyRing->allVerifyKids(), true);
    }

    /**
     * Deterministically derive + sign a token envelope for a given ticket,
     * epoch, kid, and timestamps. Shared by rotate() and render().
     */
    private function build(int $ticketId, int $epoch, string $kid, Carbon $issuedAt, Carbon $expiresAt): IssuedToken
    {
        $nonce = $this->deriveNonce($ticketId, $epoch);
        $nonceB64 = TicketKeyRing::base64UrlEncode($nonce);
        $nonceHash = hash_hmac('sha256', $nonce, $this->pepper());

        $ticket = Ticket::find($ticketId);
        $body = [
            'tid' => $ticketId,
            'nonce' => $nonceB64,
            'iat' => $issuedAt->getTimestamp(),
            'exp' => $expiresAt->getTimestamp(),
            'evt' => $ticket?->event_id,
        ];

        $bodyJson = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $bodyB64 = TicketKeyRing::base64UrlEncode($bodyJson);
        $version = (string) config('tickets.token.version', 'LCT1');
        $signingInput = "{$version}.{$kid}.{$bodyB64}";
        $signature = $this->keyRing->sign($kid, $signingInput);
        $sigB64 = TicketKeyRing::base64UrlEncode($signature);

        return new IssuedToken(
            qrPayload: "{$version}.{$kid}.{$bodyB64}.{$sigB64}",
            nonceHash: $nonceHash,
            kid: $kid,
            issuedAt: $issuedAt,
            expiresAt: $expiresAt,
        );
    }

    /**
     * nonce = HMAC-SHA256(pepper, tid_le64 || epoch_le64) truncated to 16 bytes.
     * The truncation matches the prior 128-bit CSPRNG nonce length.
     */
    private function deriveNonce(int $ticketId, int $epoch): string
    {
        $material = pack('J', $ticketId).pack('J', $epoch);
        $full = hash_hmac('sha256', $material, $this->pepper(), true);

        return substr($full, 0, 16);
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
