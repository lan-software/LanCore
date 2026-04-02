<?php

namespace App\Domain\Integration\Http\Controllers;

use App\Domain\Integration\Actions\CreateIntegrationToken;
use App\Domain\Integration\Actions\RevokeIntegrationToken;
use App\Domain\Integration\Actions\RotateIntegrationToken;
use App\Domain\Integration\Http\Requests\StoreIntegrationTokenRequest;
use App\Domain\Integration\Models\IntegrationApp;
use App\Domain\Integration\Models\IntegrationToken;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

/**
 * @see docs/mil-std-498/SSS.md CAP-INT-002
 * @see docs/mil-std-498/SRS.md INT-F-002
 */
class IntegrationTokenController extends Controller
{
    public function __construct(
        private readonly CreateIntegrationToken $createIntegrationToken,
        private readonly RevokeIntegrationToken $revokeIntegrationToken,
        private readonly RotateIntegrationToken $rotateIntegrationToken,
    ) {}

    public function store(StoreIntegrationTokenRequest $request, IntegrationApp $integration): RedirectResponse
    {
        $this->authorize('manageTokens', $integration);

        $expiresAt = $request->validated('expires_at')
            ? new \DateTimeImmutable($request->validated('expires_at'))
            : null;

        $result = $this->createIntegrationToken->execute(
            $integration,
            $request->validated('name'),
            $expiresAt,
        );

        return back()->with('newToken', $result['plain_text']);
    }

    public function destroy(IntegrationApp $integration, IntegrationToken $token): RedirectResponse
    {
        $this->authorize('manageTokens', $integration);

        if ($token->integration_app_id !== $integration->id) {
            abort(404);
        }

        $this->revokeIntegrationToken->execute($token);

        return back();
    }

    public function rotate(IntegrationApp $integration, IntegrationToken $token): RedirectResponse
    {
        $this->authorize('manageTokens', $integration);

        if ($token->integration_app_id !== $integration->id) {
            abort(404);
        }

        if (! $token->isUsable()) {
            abort(422, 'Cannot rotate a revoked or expired token.');
        }

        $result = $this->rotateIntegrationToken->execute($token);

        return back()->with('newToken', $result['plain_text']);
    }
}
