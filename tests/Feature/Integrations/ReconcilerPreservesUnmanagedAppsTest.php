<?php

use App\Domain\Integration\Actions\CreateIntegrationToken;
use App\Domain\Integration\Models\IntegrationApp;
use App\Domain\Integration\Services\LancoreIntegrationsReconciler;

beforeEach(function () {
    config()->set('integrations.domain', 'lanparty.de');
    config()->set('integrations.satellite_host_style', 'flat');
    config()->set('integrations.scheme', 'https');
});

it('leaves apps whose slug is NOT in config completely untouched', function () {
    $uiApp = IntegrationApp::factory()->create([
        'slug' => 'operator-custom-integration',
        'name' => 'Operator Custom',
    ]);
    app(CreateIntegrationToken::class)->execute($uiApp, 'ui-minted-token');

    expect($uiApp->tokens()->count())->toBe(1);

    config()->set('integrations.apps', [
        'lanbrackets' => [
            'name' => 'LanBrackets',
            'token' => 'lci_aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        ],
    ]);

    app(LancoreIntegrationsReconciler::class)->reconcile();

    $uiApp->refresh();
    expect($uiApp->name)->toBe('Operator Custom');
    expect($uiApp->tokens()->count())->toBe(1);
    expect($uiApp->tokens()->first()->name)->toBe('ui-minted-token');
});

it('reconciler reports isConfigManaged correctly', function () {
    config()->set('integrations.apps', [
        'lanbrackets' => ['name' => 'LanBrackets'],
    ]);

    $reconciler = app(LancoreIntegrationsReconciler::class);

    expect($reconciler->isConfigManaged('lanbrackets'))->toBeTrue();
    expect($reconciler->isConfigManaged('operator-custom-integration'))->toBeFalse();
});
