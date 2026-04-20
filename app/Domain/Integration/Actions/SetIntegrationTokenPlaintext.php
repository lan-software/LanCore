<?php

namespace App\Domain\Integration\Actions;

use App\Domain\Integration\Models\IntegrationApp;
use App\Domain\Integration\Models\IntegrationToken;
use DateTimeInterface;

/**
 * Persist a caller-supplied plaintext token for an integration app.
 *
 * The plaintext is hashed with SHA-256 before storage; only the hash + an
 * 8-character display prefix are written to the database. Matches the
 * storage contract of INT-F-003 and CreateIntegrationToken; unlike that
 * action this one accepts the plaintext from the caller rather than
 * generating its own, so the Helm umbrella chart can pre-provision the
 * same token into both LanCore and the satellite Secret.
 *
 * @see docs/mil-std-498/SRS.md INT-F-013
 * @see docs/mil-std-498/SSDD.md §5.4.5
 */
class SetIntegrationTokenPlaintext
{
    public function execute(
        IntegrationApp $app,
        string $name,
        string $plaintext,
        ?DateTimeInterface $expiresAt = null,
    ): IntegrationToken {
        return $app->tokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plaintext),
            'plain_text_prefix' => substr($plaintext, 0, 8),
            'expires_at' => $expiresAt,
        ]);
    }
}
