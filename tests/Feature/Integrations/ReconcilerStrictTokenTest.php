<?php

use App\Domain\Integration\Exceptions\IntegrationConfigurationException;
use App\Domain\Integration\Models\IntegrationApp;
use App\Domain\Integration\Services\LancoreIntegrationsReconciler;

beforeEach(function () {
    config()->set('integrations.domain', 'lanparty.de');
    config()->set('integrations.satellite_host_style', 'flat');
    config()->set('integrations.scheme', 'https');
});

it('throws IntegrationConfigurationException when strict and a slug has no token', function () {
    config()->set('integrations.apps', [
        'lanhelp' => [
            'name' => 'LanHelp',
            'send_role_updates' => true,
            // token intentionally absent
        ],
    ]);

    app(LancoreIntegrationsReconciler::class)->reconcile([], false, true);
})->throws(IntegrationConfigurationException::class, "Missing integration token for slug 'lanhelp'");

it('does not throw in non-strict mode when a slug has no token', function () {
    config()->set('integrations.apps', [
        'lanhelp' => [
            'name' => 'LanHelp',
            'send_role_updates' => true,
        ],
    ]);

    $summary = app(LancoreIntegrationsReconciler::class)->reconcile();

    expect($summary)->toHaveCount(1);
    expect($summary[0]['token_rotated'])->toBeFalse();
    expect(IntegrationApp::where('slug', 'lanhelp')->exists())->toBeTrue();
});

it('strict mode passes when every configured slug supplies a token', function () {
    config()->set('integrations.apps', [
        'lanshout' => [
            'name' => 'LanShout',
            'token' => 'lci_strict-pass-aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        ],
    ]);

    $summary = app(LancoreIntegrationsReconciler::class)->reconcile([], false, true);

    expect($summary[0]['token_rotated'])->toBeTrue();
});

it('the artisan command rejects missing tokens by default', function () {
    config()->set('integrations.apps', [
        'lanhelp' => [
            'name' => 'LanHelp',
        ],
    ]);

    $this->artisan('integrations:sync')
        ->assertFailed();
});

it('the artisan command tolerates missing tokens with --allow-missing-tokens', function () {
    config()->set('integrations.apps', [
        'lanhelp' => [
            'name' => 'LanHelp',
        ],
    ]);

    $this->artisan('integrations:sync', ['--allow-missing-tokens' => true])
        ->assertSuccessful();

    expect(IntegrationApp::where('slug', 'lanhelp')->exists())->toBeTrue();
});
