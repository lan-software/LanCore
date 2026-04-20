<?php

use App\Domain\Integration\Models\IntegrationApp;

beforeEach(function () {
    config()->set('integrations.domain', 'lanparty.de');
    config()->set('integrations.satellite_host_style', 'flat');
    config()->set('integrations.scheme', 'https');
});

it('does not write to the database in --dry-run mode', function () {
    config()->set('integrations.apps', [
        'lanbrackets' => [
            'name' => 'LanBrackets',
            'token' => 'lci_aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        ],
    ]);

    expect(IntegrationApp::count())->toBe(0);

    $this->artisan('integrations:sync', ['--dry-run' => true])
        ->assertSuccessful();

    expect(IntegrationApp::count())->toBe(0);
});

it('applies the reconciliation without --dry-run', function () {
    config()->set('integrations.apps', [
        'lanshout' => [
            'name' => 'LanShout',
            'token' => 'lci_aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        ],
    ]);

    $this->artisan('integrations:sync')
        ->assertSuccessful();

    expect(IntegrationApp::where('slug', 'lanshout')->exists())->toBeTrue();
});

it('respects --only to filter slugs', function () {
    config()->set('integrations.apps', [
        'lanbrackets' => [
            'name' => 'LanBrackets',
            'token' => 'lci_aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        ],
        'lanhelp' => [
            'name' => 'LanHelp',
            'token' => 'lci_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
        ],
    ]);

    $this->artisan('integrations:sync', ['--only' => ['lanbrackets']])
        ->assertSuccessful();

    expect(IntegrationApp::where('slug', 'lanbrackets')->exists())->toBeTrue();
    expect(IntegrationApp::where('slug', 'lanhelp')->exists())->toBeFalse();
});
