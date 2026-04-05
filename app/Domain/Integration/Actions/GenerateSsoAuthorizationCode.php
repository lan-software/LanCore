<?php

namespace App\Domain\Integration\Actions;

use App\Domain\Integration\Models\IntegrationApp;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * @see docs/mil-std-498/SSS.md CAP-INT-003
 * @see docs/mil-std-498/SRS.md INT-F-004
 */
class GenerateSsoAuthorizationCode
{
    /**
     * Generate a short-lived, single-use SSO authorization code.
     */
    public function execute(User $user, IntegrationApp $app): string
    {
        $code = Str::random(64);

        Cache::put(
            "sso_code:{$code}",
            [
                'user_id' => $user->id,
                'integration_app_id' => $app->id,
            ],
            now()->addMinutes(5),
        );

        return $code;
    }
}
