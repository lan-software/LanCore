<?php

namespace App\Domain\Integration\Actions;

use App\Domain\Integration\Models\IntegrationToken;

class RevokeIntegrationToken
{
    public function execute(IntegrationToken $token): void
    {
        $token->update(['revoked_at' => now()]);
    }
}
