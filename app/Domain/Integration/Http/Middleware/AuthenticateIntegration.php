<?php

namespace App\Domain\Integration\Http\Middleware;

use App\Domain\Integration\Models\IntegrationToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @see docs/mil-std-498/SRS.md SEC-009, INT-F-005
 * @see docs/mil-std-498/IRS.md IF-INTAPI-002, IF-INTAPI-003
 */
class AuthenticateIntegration
{
    /**
     * Authenticate a request using an integration bearer token.
     *
     * Sets `integration_app` and `integration_token` on the request attributes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $bearerToken = $request->bearerToken();

        if (! $bearerToken || ! str_starts_with($bearerToken, 'lci_')) {
            return response()->json(['error' => 'Missing or invalid integration token'], 401);
        }

        $hashedToken = hash('sha256', $bearerToken);

        $token = IntegrationToken::with('integrationApp')
            ->where('token', $hashedToken)
            ->first();

        if (! $token) {
            return response()->json(['error' => 'Invalid integration token'], 401);
        }

        if (! $token->isUsable()) {
            return response()->json(['error' => 'Token is revoked or expired'], 403);
        }

        $app = $token->integrationApp;

        if (! $app->is_active) {
            return response()->json(['error' => 'Integration app is inactive'], 403);
        }

        $token->forceFill(['last_used_at' => now()])->saveQuietly();

        $request->attributes->set('integration_app', $app);
        $request->attributes->set('integration_token', $token);

        return $next($request);
    }
}
