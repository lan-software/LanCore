<?php

use App\Domain\Integration\Actions\CreateIntegrationToken;
use App\Domain\Integration\Models\IntegrationApp;
use App\Domain\Integration\Services\LancoreIntegrationsReconciler;

beforeEach(function () {
    config()->set('integrations.domain', 'lanparty.de');
    config()->set('integrations.satellite_host_style', 'flat');
    config()->set('integrations.scheme', 'https');
});

it('wipes pre-existing tokens and leaves only the config-seeded one', function () {
    // Seed a pre-existing app with two tokens via the imperative path.
    $existingApp = IntegrationApp::factory()->create([
        'slug' => 'lanbrackets',
        'name' => 'LanBrackets (stale)',
    ]);

    $createToken = app(CreateIntegrationToken::class);
    $createToken->execute($existingApp, 'pre-existing-dev');
    $createToken->execute($existingApp, 'pre-existing-prod');

    expect($existingApp->tokens()->count())->toBe(2);

    // Now reconcile from config — should delete both tokens and insert the
    // config-seeded one.
    config()->set('integrations.apps', [
        'lanbrackets' => [
            'name' => 'LanBrackets',
            'token' => 'lci_fresh-token-aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        ],
    ]);

    app(LancoreIntegrationsReconciler::class)->reconcile();

    $existingApp->refresh();
    expect($existingApp->tokens()->count())->toBe(1);
    expect($existingApp->tokens()->first()->name)->toBe('config-seeded');
    expect($existingApp->name)->toBe('LanBrackets', 'name should be updated from config');
});
