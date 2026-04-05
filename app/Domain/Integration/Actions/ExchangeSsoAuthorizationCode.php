<?php

namespace App\Domain\Integration\Actions;

use App\Domain\Integration\Models\IntegrationApp;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * @see docs/mil-std-498/SSS.md CAP-INT-003
 * @see docs/mil-std-498/SRS.md INT-F-004, INT-F-005
 */
class ExchangeSsoAuthorizationCode
{
    public function __construct(
        private readonly ResolveIntegrationUser $resolveIntegrationUser,
    ) {}

    /**
     * Exchange an SSO authorization code for user data.
     *
     * @return array{error: string}|array{data: array<string, mixed>}
     */
    public function execute(string $code, IntegrationApp $app): array
    {
        $cacheKey = "sso_code:{$code}";
        $payload = Cache::pull($cacheKey);

        if (! $payload) {
            return ['error' => 'Invalid or expired authorization code'];
        }

        if ($payload['integration_app_id'] !== $app->id) {
            return ['error' => 'Authorization code does not belong to this application'];
        }

        $user = User::find($payload['user_id']);

        if (! $user) {
            return ['error' => 'User not found'];
        }

        $data = $this->resolveIntegrationUser->execute($user, $app);

        if ($data === null) {
            return ['error' => 'Insufficient scopes'];
        }

        return ['data' => $data];
    }
}
