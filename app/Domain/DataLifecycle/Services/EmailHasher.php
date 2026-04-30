<?php

namespace App\Domain\DataLifecycle\Services;

use Illuminate\Contracts\Config\Repository as Config;
use RuntimeException;

/**
 * Deterministic, salted one-way hash of an email address.
 *
 * Survives in-place anonymization on the User row so that GDPR Article 15
 * exports can still locate a deleted-and-anonymized account by the original
 * email. The HKDF context string is versioned to allow future re-hashing of
 * the column should the secret ever be compromised.
 *
 * @see docs/mil-std-498/SSS.md CAP-DL-007
 * @see docs/mil-std-498/SRS.md DL-F-016
 * @see docs/mil-std-498/IRS.md IF-DL-001
 */
final class EmailHasher
{
    private const HKDF_CONTEXT = 'data-lifecycle-email-v1';

    public function __construct(private Config $config) {}

    /**
     * Compute the hex-encoded HMAC-SHA256 hash of an email address.
     *
     * The input is lower-cased and trimmed before hashing so that minor
     * whitespace/case differences across the codebase produce identical
     * hashes. Returns 64 hex characters.
     */
    public function hash(string $email): string
    {
        $normalized = mb_strtolower(trim($email));

        return hash_hmac('sha256', $normalized, $this->derivedKey());
    }

    private function derivedKey(): string
    {
        $appKey = (string) $this->config->get('app.key');

        if ($appKey === '') {
            throw new RuntimeException('app.key must be set before hashing emails.');
        }

        if (str_starts_with($appKey, 'base64:')) {
            $appKey = base64_decode(substr($appKey, 7), true) ?: '';
        }

        return hash_hkdf('sha256', $appKey, 32, self::HKDF_CONTEXT);
    }
}
