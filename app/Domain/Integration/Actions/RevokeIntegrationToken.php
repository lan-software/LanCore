<?php

namespace App\Domain\Integration\Actions;

use App\Domain\Integration\Models\IntegrationToken;

/**
 * @see docs/mil-std-498/SRS.md INT-F-002
 */
class RevokeIntegrationToken
{
    public function execute(IntegrationToken $token): void
    {
        $token->update(['revoked_at' => now()]);
    }
}
