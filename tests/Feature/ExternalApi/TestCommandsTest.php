<?php

use Illuminate\Support\Facades\Http;

it('returns 0 when listmonk is connected', function (): void {
    config()->set('listmonk.enabled', true);
    config()->set('listmonk.base_url', 'https://listmonk.test');
    config()->set('listmonk.username', 'admin');
    config()->set('listmonk.password', 'secret');

    Http::fake([
        'listmonk.test/api/health' => Http::response(['data' => ['version' => 'v3.0.0']], 200),
    ]);

    $this->artisan('external-apis:test:listmonk')->assertExitCode(0);
});

it('returns 2 when listmonk is not configured', function (): void {
    config()->set('listmonk.enabled', false);

    $this->artisan('external-apis:test:listmonk')->assertExitCode(2);
});

it('returns 2 when steam is not configured', function (): void {
    config()->set('services.steam.client_secret', '');

    $this->artisan('external-apis:test:steam')->assertExitCode(2);
});
