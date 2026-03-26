<?php

namespace App\Domain\Integration\Actions;

use App\Domain\Integration\Models\IntegrationToken;

class RotateIntegrationToken
{
    public function __construct(
        private readonly RevokeIntegrationToken $revokeIntegrationToken,
        private readonly CreateIntegrationToken $createIntegrationToken,
    ) {}

    /**
     * Revoke an existing token and create a replacement with the same name and expiration policy.
     *
     * @return array{token: IntegrationToken, plain_text: string}
     */
    public function execute(IntegrationToken $existingToken, ?\DateTimeInterface $expiresAt = null): array
    {
        $this->revokeIntegrationToken->execute($existingToken);

        return $this->createIntegrationToken->execute(
            $existingToken->integrationApp,
            $existingToken->name,
            $expiresAt,
        );
    }
}
