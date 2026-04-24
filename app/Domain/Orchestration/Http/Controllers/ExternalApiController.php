<?php

namespace App\Domain\Orchestration\Http\Controllers;

use App\Domain\Api\Clients\Tmt2Client;
use App\Domain\Orchestration\Models\GameServer;
use App\Domain\Shop\Support\CurrencyResolver;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Balance;
use Stripe\Exception\AuthenticationException;
use Stripe\Stripe;

/**
 * Admin page for managing external API connections (TMT2, future: Pelican, Steam).
 */
class ExternalApiController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', GameServer::class);

        $stripeKey = (string) config('cashier.key');
        $stripeSecret = (string) config('cashier.secret');

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

        try {
            $client = app(Tmt2Client::class);
            $result = $client->login();

            if ($result) {
                return response()->json(['status' => 'connected']);
            }

            return response()->json(['status' => 'auth_failed'], 401);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'unreachable', 'error' => $e->getMessage()], 503);
        }
    }

    public function testStripe(Request $request): JsonResponse
    {
        $this->authorize('viewAny', GameServer::class);

        $secret = (string) config('cashier.secret');

        if ($secret === '') {
            return response()->json(['status' => 'not_configured', 'error' => 'STRIPE_SECRET is not set.'], 422);
        }

        try {
            Stripe::setApiKey($secret);
            $balance = Balance::retrieve();

            return response()->json([
                'status' => 'connected',
                'account' => count($balance->available) > 0
                    ? strtoupper($balance->available[0]->currency).' account'
                    : 'OK',
            ]);
        } catch (AuthenticationException) {
            return response()->json(['status' => 'auth_failed', 'error' => 'Invalid API key.'], 401);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'unreachable', 'error' => $e->getMessage()], 503);
        }
    }
}
