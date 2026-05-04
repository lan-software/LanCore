<?php

use App\Domain\Orchestration\Services\ExternalApiTester;
use Illuminate\Support\Facades\Http;

it('returns connected for a successful Steam probe', function (): void {
    config()->set('services.steam.client_secret', 'fake-key');

    Http::fake([
        'api.steampowered.com/*' => Http::response(['response' => ['success' => 1, 'steamid' => '123']], 200),
    ]);

    expect(app(ExternalApiTester::class)->steam())->toMatchArray([
        'status' => 'connected',
        'account' => 'gabelogannewell',
    ]);
});

it('reports auth_failed when Steam rejects the API key', function (): void {
    config()->set('services.steam.client_secret', 'fake-key');

    Http::fake([
        'api.steampowered.com/*' => Http::response(['response' => ['success' => 42]], 200),
    ]);

    expect(app(ExternalApiTester::class)->steam()['status'])->toBe('auth_failed');
});

it('reports not_configured when Steam API key is missing', function (): void {
    config()->set('services.steam.client_secret', '');

    expect(app(ExternalApiTester::class)->steam()['status'])->toBe('not_configured');
});

it('reports not_configured when Listmonk is disabled', function (): void {
    config()->set('listmonk.enabled', false);

    expect(app(ExternalApiTester::class)->listmonk()['status'])->toBe('not_configured');
});

it('returns connected for a Listmonk health probe', function (): void {
    config()->set('listmonk.enabled', true);
    config()->set('listmonk.base_url', 'https://listmonk.test');
    config()->set('listmonk.username', 'admin');
    config()->set('listmonk.password', 'secret');

    Http::fake([
        'listmonk.test/api/health' => Http::response(['data' => ['version' => 'v3.0.0']], 200),
    ]);

    expect(app(ExternalApiTester::class)->listmonk())->toMatchArray([
        'status' => 'connected',
        'account' => 'v3.0.0',
    ]);
});
