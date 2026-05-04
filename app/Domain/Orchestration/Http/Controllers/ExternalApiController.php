<?php

namespace App\Domain\Orchestration\Http\Controllers;

use App\Domain\Orchestration\Models\GameServer;
use App\Domain\Orchestration\Services\ExternalApiTester;
use App\Domain\Shop\Support\CurrencyResolver;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin page for managing external API connections (TMT2, Stripe, PayPal,
 * Steam, Listmonk). Each card surfaces a connectivity test that mirrors the
 * `external-apis:test:*` console commands; both delegate to
 * {@see ExternalApiTester} so the result shape stays uniform.
 *
 * @see docs/mil-std-498/SSS.md CAP-ORC-011
 * @see docs/mil-std-498/SRS.md EXT-F-001..005
 */
class ExternalApiController extends Controller
{
    public function __construct(private readonly ExternalApiTester $tester) {}

    public function index(): Response
    {
        $this->authorize('viewAny', GameServer::class);

        $stripeKey = (string) config('cashier.key');
        $stripeSecret = (string) config('cashier.secret');
        $steamKey = (string) config('services.steam.client_secret');

        return Inertia::render('orchestration/apis/Index', [
            'connections' => [
                'tmt2' => [
                    'enabled' => config('tmt2.enabled'),
                    'base_url' => config('tmt2.base_url'),
                    'has_token' => config('tmt2.token') !== null && config('tmt2.token') !== '',
                    'timeout' => config('tmt2.timeout', 5),
                    'retries' => config('tmt2.retries', 2),
                ],
                'stripe' => [
                    'enabled' => $stripeKey !== '' && $stripeSecret !== '',
                    'has_publishable_key' => $stripeKey !== '',
                    'has_secret_key' => $stripeSecret !== '',
                    'has_webhook_secret' => ((string) config('cashier.webhook.secret')) !== '',
                    'currency' => CurrencyResolver::upperCode(),
                    'currency_locale' => config('cashier.currency_locale', 'en'),
                ],
                'paypal' => $this->paypalStatus(),
                'steam' => [
                    'enabled' => $steamKey !== '',
                    'has_api_key' => $steamKey !== '',
                    'has_redirect_uri' => ((string) config('services.steam.redirect', '')) !== '',
                ],
                'listmonk' => [
                    'enabled' => (bool) config('listmonk.enabled'),
                    'base_url' => (string) config('listmonk.base_url', ''),
                    'has_username' => ((string) config('listmonk.username', '')) !== '',
                    'has_password' => ((string) config('listmonk.password', '')) !== '',
                    'preconfirm_subscriptions' => (bool) config('listmonk.preconfirm_subscriptions'),
                ],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function paypalStatus(): array
    {
        $mode = (string) config('paypal.mode', 'sandbox');
        $envCreds = (array) config("paypal.{$mode}", []);
        $clientId = (string) ($envCreds['client_id'] ?? '');
        $clientSecret = (string) ($envCreds['client_secret'] ?? '');
        $webhookId = (string) config('paypal.webhook_id', '');

        return [
            'enabled' => $clientId !== '' && $clientSecret !== '',
            'mode' => $mode,
            'has_client_id' => $clientId !== '',
            'has_client_secret' => $clientSecret !== '',
            'has_webhook_id' => $webhookId !== '',
        ];
    }

    public function testTmt2(Request $request): JsonResponse
    {
        $this->authorize('viewAny', GameServer::class);

        return $this->respond($this->tester->tmt2());
    }

    public function testStripe(Request $request): JsonResponse
    {
        $this->authorize('viewAny', GameServer::class);

        return $this->respond($this->tester->stripe());
    }

    public function testPaypal(Request $request): JsonResponse
    {
        $this->authorize('viewAny', GameServer::class);

        return $this->respond($this->tester->paypal());
    }

    public function testSteam(Request $request): JsonResponse
    {
        $this->authorize('viewAny', GameServer::class);

        return $this->respond($this->tester->steam());
    }

    public function testListmonk(Request $request): JsonResponse
    {
        $this->authorize('viewAny', GameServer::class);

        return $this->respond($this->tester->listmonk());
    }

    /**
     * @param  array{status: string, account?: string, error?: string}  $result
     */
    private function respond(array $result): JsonResponse
    {
        $status = match ($result['status']) {
            ExternalApiTester::STATUS_CONNECTED => 200,
            ExternalApiTester::STATUS_AUTH_FAILED => 401,
            ExternalApiTester::STATUS_NOT_CONFIGURED => 422,
            default => 503,
        };

        return response()->json($result, $status);
    }
}
