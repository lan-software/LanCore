<?php

namespace App\Domain\Integration\Actions;

use App\Domain\Integration\Models\IntegrationApp;
use App\Domain\Integration\Models\IntegrationToken;
use Illuminate\Support\Str;

class CreateIntegrationToken
{
    /**
     * Create a new token for the given integration app.
     *
     * Returns a tuple of [IntegrationToken, plainTextToken].
     * The plain-text token is only available at creation time.
     *
     * @return array{token: IntegrationToken, plain_text: string}
     */
    public function execute(IntegrationApp $app, string $name, ?\DateTimeInterface $expiresAt = null): array
    {
        $plainText = 'lci_'.Str::random(60);

        $token = $app->tokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plainText),
            'plain_text_prefix' => substr($plainText, 0, 8),
            'expires_at' => $expiresAt,
        ]);

        return [
            'token' => $token,
            'plain_text' => $plainText,
        ];
    }
}
