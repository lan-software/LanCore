<?php

use App\Domain\Integration\Models\IntegrationApp;
use App\Domain\Integration\Services\LancoreIntegrationsReconciler;

beforeEach(function () {
    config()->set('integrations.domain', 'lanparty.de');
    config()->set('integrations.satellite_host_style', 'flat');
    config()->set('integrations.scheme', 'https');
});

it('creates an IntegrationApp from config when none exists', function () {
    config()->set('integrations.apps', [
        'lanbrackets' => [
            'name' => 'LanBrackets',
            'scopes' => ['user:read', 'user:email'],
            'send_role_updates' => true,
            'token' => 'lci_test-token-plaintext-aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        ],
    ]);

    $reconciler = app(LancoreIntegrationsReconciler::class);

    $summary = $reconciler->reconcile();

    expect($summary)->toHaveCount(1);
    expect($summary[0]['slug'])->toBe('lanbrackets');
    expect($summary[0]['created'])->toBeTrue();
    expect($summary[0]['token_rotated'])->toBeTrue();

    $app = IntegrationApp::where('slug', 'lanbrackets')->firstOrFail();
    expect($app->callback_url)->toBe('https://lanbrackets.lanparty.de/auth/callback');
    expect($app->allowed_scopes)->toBe(['user:read', 'user:email']);
    expect($app->tokens()->count())->toBe(1);
    expect($app->tokens->first()->name)->toBe('config-seeded');
});

it('is idempotent — running twice leaves a single token', function () {
    config()->set('integrations.apps', [
        'lanshout' => [
            'name' => 'LanShout',
            'scopes' => ['user:read'],
            'token' => 'lci_stable-token-aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        ],
    ]);

    $reconciler = app(LancoreIntegrationsReconciler::class);
    $reconciler->reconcile();
    $reconciler->reconcile();

    $app = IntegrationApp::where('slug', 'lanshout')->firstOrFail();
    expect($app->tokens()->count())->toBe(1, 'only the config-seeded token should survive');
});

it('rotates the token when the config plaintext changes', function () {
    config()->set('integrations.apps', [
        'lanhelp' => [
            'name' => 'LanHelp',
            'token' => 'lci_plaintext-one-aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        ],
    ]);

    $reconciler = app(LancoreIntegrationsReconciler::class);
    $reconciler->reconcile();

    $firstHash = IntegrationApp::where('slug', 'lanhelp')->firstOrFail()
        ->tokens()->firstOrFail()->token;

    config()->set('integrations.apps.lanhelp.token', 'lci_plaintext-two-aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
    $reconciler->reconcile();

    $secondHash = IntegrationApp::where('slug', 'lanhelp')->firstOrFail()
        ->tokens()->firstOrFail()->token;

    expect($secondHash)->not->toBe($firstHash);
    expect(IntegrationApp::where('slug', 'lanhelp')->firstOrFail()->tokens()->count())->toBe(1);
});

it('expands flat host style to <slug>.<domain>', function () {
    config()->set('integrations.satellite_host_style', 'flat');
    config()->set('integrations.apps', [
        'lanbrackets' => [
            'name' => 'LanBrackets',
            'token' => 'lci_aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        ],
    ]);

    app(LancoreIntegrationsReconciler::class)->reconcile();

    expect(IntegrationApp::where('slug', 'lanbrackets')->firstOrFail()->callback_url)
        ->toBe('https://lanbrackets.lanparty.de/auth/callback');
});

it('expands prefixed host style to <slug>.lancore.<domain>', function () {
    config()->set('integrations.satellite_host_style', 'prefixed');
    config()->set('integrations.apps', [
        'lanbrackets' => [
            'name' => 'LanBrackets',
            'token' => 'lci_aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        ],
    ]);

    app(LancoreIntegrationsReconciler::class)->reconcile();

    expect(IntegrationApp::where('slug', 'lanbrackets')->firstOrFail()->callback_url)
        ->toBe('https://lanbrackets.lancore.lanparty.de/auth/callback');
});
