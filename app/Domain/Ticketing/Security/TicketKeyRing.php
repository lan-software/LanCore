<?php

namespace App\Domain\Ticketing\Security;

use App\Domain\Ticketing\Security\Exceptions\UnknownKidException;

/**
 * Loads Ed25519 keypairs for ticket signing/verification.
 *
 * Key files live in `config('tickets.signing.keys_path')` as `{kid}.key`,
 * holding raw bytes in `sodium_crypto_sign_keypair()` layout
 * (64-byte secret key concatenated with 32-byte public key).
 *
 * @see docs/mil-std-498/SDD.md §3.3.2
 * @see docs/mil-std-498/SSS.md SEC-014..020
 */
class TicketKeyRing
{
    /** @var array<string, string> */
    private array $keypairs = [];

    private bool $loaded = false;

    public function sign(string $kid, string $message): string
    {
        $this->ensureLoaded();
        $keypair = $this->keypairs[$kid] ?? null;

        if ($keypair === null) {
            throw new UnknownKidException("Unknown signing kid: {$kid}");
        }

        $secret = sodium_crypto_sign_secretkey($keypair);

        return sodium_crypto_sign_detached($message, $secret);
    }

    public function publicKey(string $kid): string
    {
        $this->ensureLoaded();
        $keypair = $this->keypairs[$kid] ?? null;

        if ($keypair === null) {
            throw new UnknownKidException("Unknown verify kid: {$kid}");
        }

        return sodium_crypto_sign_publickey($keypair);
    }

    public function activeKid(): string
    {
        $kid = (string) config('tickets.signing.active_kid');

        if ($kid === '') {
            throw new UnknownKidException('No active signing kid configured.');
        }

        return $kid;
    }

    /**
     * @return array<int, string>
     */
    public function allVerifyKids(): array
    {
        $this->ensureLoaded();

        return array_keys($this->keypairs);
    }

    /**
     * @return array{keys: array<int, array{kid: string, kty: string, crv: string, x: string}>}
     */
    public function toJwks(): array
    {
        $this->ensureLoaded();

        $keys = [];
        foreach ($this->keypairs as $kid => $keypair) {
            $pub = sodium_crypto_sign_publickey($keypair);
            $keys[] = [
                'kid' => $kid,
                'kty' => 'OKP',
                'crv' => 'Ed25519',
                'x' => self::base64UrlEncode($pub),
            ];
        }

        return ['keys' => $keys];
    }

    public static function base64UrlEncode(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $value): string
    {
        $padded = strtr($value, '-_', '+/');
        $remainder = strlen($padded) % 4;

        if ($remainder !== 0) {
            $padded .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode($padded, true);

        return $decoded === false ? '' : $decoded;
    }

    private function ensureLoaded(): void
    {
        if ($this->loaded) {
            return;
        }

        $path = (string) config('tickets.signing.keys_path');
        $verifyKids = (array) config('tickets.signing.verify_kids', []);
        $activeKid = (string) config('tickets.signing.active_kid', '');

        if ($activeKid !== '' && ! in_array($activeKid, $verifyKids, true)) {
            $verifyKids[] = $activeKid;
        }

        foreach ($verifyKids as $kid) {
            $kid = (string) $kid;
            if ($kid === '') {
                continue;
            }

            $file = $path.DIRECTORY_SEPARATOR.$kid.'.key';
            if (! is_file($file)) {
                continue;
            }

            $bytes = (string) file_get_contents($file);
            if (strlen($bytes) === SODIUM_CRYPTO_SIGN_KEYPAIRBYTES) {
                $this->keypairs[$kid] = $bytes;
            }
        }

        $this->loaded = true;
    }
}
