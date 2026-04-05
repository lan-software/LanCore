<?php

namespace App\Domain\Integration\Http\Controllers;

use App\Domain\Integration\Actions\ExchangeSsoAuthorizationCode;
use App\Domain\Integration\Actions\GenerateSsoAuthorizationCode;
use App\Domain\Integration\Events\IntegrationAccessed;
use App\Domain\Integration\Models\IntegrationApp;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * @see docs/mil-std-498/SSS.md CAP-INT-003
 * @see docs/mil-std-498/SRS.md INT-F-004, INT-F-005
 */
class IntegrationSsoController extends Controller
{
    public function __construct(
        private readonly GenerateSsoAuthorizationCode $generateCode,
        private readonly ExchangeSsoAuthorizationCode $exchangeCode,
    ) {}

    /**
     * SSO authorize endpoint — redirect the user back to the integration app with an auth code.
     *
     * GET /sso/authorize?app={slug}&redirect_uri={url}
     */
    public function redirectWithCode(Request $request): RedirectResponse
    {
        $request->validate([
            'app' => ['required', 'string'],
            'redirect_uri' => ['required', 'url'],
        ]);

        $app = IntegrationApp::where('slug', $request->string('app'))->first();

        if (! $app || ! $app->is_active) {
            abort(404, 'Integration app not found or inactive.');
        }

        if (! $app->callback_url || ! str_starts_with($request->string('redirect_uri')->toString(), $app->callback_url)) {
            abort(403, 'Redirect URI does not match the registered callback URL.');
        }

        /** @var User $user */
        $user = $request->user();

        $code = $this->generateCode->execute($user, $app);

        IntegrationAccessed::dispatch($user, $app);

        $separator = str_contains($request->string('redirect_uri')->toString(), '?') ? '&' : '?';

        return redirect()->away(
            $request->string('redirect_uri')->toString().$separator.'code='.$code,
        );
    }

    /**
     * Exchange an SSO authorization code for user data.
     *
     * POST /api/integration/sso/exchange
     */
    public function exchange(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:64'],
        ]);

        /** @var IntegrationApp $app */
        $app = $request->attributes->get('integration_app');

        $result = $this->exchangeCode->execute($request->string('code')->toString(), $app);

        if (isset($result['error'])) {
            $status = match ($result['error']) {
                'Invalid or expired authorization code' => 400,
                'Authorization code does not belong to this application' => 403,
                'Insufficient scopes' => 403,
                default => 400,
            };

            return response()->json(['error' => $result['error']], $status);
        }

        return response()->json($result);
    }
}
