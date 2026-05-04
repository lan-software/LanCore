<?php

namespace App\Domain\Orchestration\Services;

use App\Domain\Api\Clients\Tmt2Client;
use App\Domain\Newsletter\Clients\ListmonkClient;
use App\Domain\Newsletter\Exceptions\ListmonkException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Stripe\Balance;
use Stripe\Exception\AuthenticationException;
use Stripe\Stripe;
use Throwable;

/**
 * Shared connectivity-test logic backing both the External API admin page
 * (HTTP buttons) and the `external-apis:test:*` console commands. Returns
 * a uniform `{status, account?, error?}` shape for every probe.
 *
 * Status values:
 *  - `connected`       service responded successfully
 *  - `auth_failed`     credentials were rejected
 *  - `not_configured`  required env keys are missing
 *  - `unreachable`     network failure or non-2xx without auth signal
 *
 * @see docs/mil-std-498/SSS.md CAP-ORC-011
 * @see docs/mil-std-498/SRS.md EXT-F-001..005
 */
class ExternalApiTester
{
    public const STATUS_CONNECTED = 'connected';

    public const STATUS_AUTH_FAILED = 'auth_failed';

    public const STATUS_NOT_CONFIGURED = 'not_configured';

    public const STATUS_UNREACHABLE = 'unreachable';

    public function __construct(private readonly ListmonkClient $listmonk) {}

    /**
     * @return array{status: string, account?: string, error?: string}
     */
    public function tmt2(): array
    {
        try {
            $client = app(Tmt2Client::class);
            $ok = $client->login();

            return $ok
                ? ['status' => self::STATUS_CONNECTED, 'account' => 'TMT2']
                : ['status' => self::STATUS_AUTH_FAILED, 'error' => 'Login rejected.'];
        } catch (Throwable $e) {
            return ['status' => self::STATUS_UNREACHABLE, 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array{status: string, account?: string, error?: string}
     */
    public function stripe(): array
    {
        $secret = (string) config('cashier.secret');

        if ($secret === '') {
            return ['status' => self::STATUS_NOT_CONFIGURED, 'error' => 'STRIPE_SECRET is not set.'];
        }

        try {
            Stripe::setApiKey($secret);
            $balance = Balance::retrieve();

            return [
                'status' => self::STATUS_CONNECTED,
                'account' => count($balance->available) > 0
                    ? strtoupper($balance->available[0]->currency).' account'
                    : 'OK',
            ];
        } catch (AuthenticationException) {
            return ['status' => self::STATUS_AUTH_FAILED, 'error' => 'Invalid API key.'];
        } catch (Throwable $e) {
            return ['status' => self::STATUS_UNREACHABLE, 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array{status: string, account?: string, error?: string}
     */
    public function paypal(): array
    {
        $mode = (string) config('paypal.mode', 'sandbox');
        $clientId = (string) config("paypal.{$mode}.client_id", '');
        $clientSecret = (string) config("paypal.{$mode}.client_secret", '');

        if ($clientId === '' || $clientSecret === '') {
            return [
                'status' => self::STATUS_NOT_CONFIGURED,
                'error' => "PAYPAL_{$mode} client id/secret are not set.",
            ];
        }

        $base = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        try {
            $response = Http::asForm()
                ->withBasicAuth($clientId, $clientSecret)
                ->timeout(5)
                ->post("{$base}/v1/oauth2/token", ['grant_type' => 'client_credentials']);

            if ($response->status() === 401 || $response->status() === 403) {
                return ['status' => self::STATUS_AUTH_FAILED, 'error' => 'Invalid PayPal client credentials.'];
            }

            if (! $response->successful()) {
                return ['status' => self::STATUS_UNREACHABLE, 'error' => "HTTP {$response->status()}"];
            }

            return ['status' => self::STATUS_CONNECTED, 'account' => $mode];
        } catch (ConnectionException $e) {
            return ['status' => self::STATUS_UNREACHABLE, 'error' => $e->getMessage()];
        } catch (Throwable $e) {
            return ['status' => self::STATUS_UNREACHABLE, 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array{status: string, account?: string, error?: string}
     */
    public function steam(): array
    {
        $apiKey = (string) config('services.steam.client_secret');

        if ($apiKey === '') {
            return ['status' => self::STATUS_NOT_CONFIGURED, 'error' => 'STEAM_API_KEY is not set.'];
        }

        try {
            $response = Http::timeout(5)
                ->get('https://api.steampowered.com/ISteamUser/ResolveVanityURL/v1/', [
                    'key' => $apiKey,
                    'vanityurl' => 'gabelogannewell',
                ]);

            if ($response->status() === 401 || $response->status() === 403) {
                return ['status' => self::STATUS_AUTH_FAILED, 'error' => 'Invalid Steam Web API key.'];
            }

            if (! $response->successful()) {
                return ['status' => self::STATUS_UNREACHABLE, 'error' => "HTTP {$response->status()}"];
            }

            $payload = $response->json();
            $success = (int) ($payload['response']['success'] ?? 0) === 1;

            if (! $success) {
                return ['status' => self::STATUS_AUTH_FAILED, 'error' => 'Steam rejected the API key.'];
            }

            return ['status' => self::STATUS_CONNECTED, 'account' => 'gabelogannewell'];
        } catch (ConnectionException $e) {
            return ['status' => self::STATUS_UNREACHABLE, 'error' => $e->getMessage()];
        } catch (Throwable $e) {
            return ['status' => self::STATUS_UNREACHABLE, 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array{status: string, account?: string, error?: string}
     */
    public function listmonk(): array
    {
        try {
            $payload = $this->listmonk->health();

            return [
                'status' => self::STATUS_CONNECTED,
                'account' => is_string($payload['version'] ?? null) ? $payload['version'] : 'OK',
            ];
        } catch (ListmonkException $e) {
            return match ($e->kind) {
                ListmonkException::KIND_NOT_CONFIGURED => [
                    'status' => self::STATUS_NOT_CONFIGURED,
                    'error' => $e->getMessage(),
                ],
                ListmonkException::KIND_AUTH_FAILED => [
                    'status' => self::STATUS_AUTH_FAILED,
                    'error' => $e->getMessage(),
                ],
                default => [
                    'status' => self::STATUS_UNREACHABLE,
                    'error' => $e->getMessage(),
                ],
            };
        } catch (Throwable $e) {
            return ['status' => self::STATUS_UNREACHABLE, 'error' => $e->getMessage()];
        }
    }
}
