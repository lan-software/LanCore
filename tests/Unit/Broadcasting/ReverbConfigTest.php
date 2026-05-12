<?php

it('exposes a populated reverb config with at least one app', function (): void {
    expect(config('reverb.default'))->toBe('reverb');

    $apps = config('reverb.apps.apps');
    expect($apps)->toBeArray()->not->toBeEmpty();

    $app = $apps[0];
    expect($app)
        ->toHaveKeys(['key', 'secret', 'app_id', 'options'])
        ->and($app['options'])->toHaveKeys(['host', 'port', 'scheme']);
});

it('declares the reverb broadcasting connection', function (): void {
    expect(config('broadcasting.connections.reverb'))
        ->toBeArray()
        ->toHaveKey('driver', 'reverb');
});

it('registers routes/channels.php', function (): void {
    expect(file_exists(base_path('routes/channels.php')))->toBeTrue();
});
