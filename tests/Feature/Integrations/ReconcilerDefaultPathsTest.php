<?php

use App\Domain\Integration\Models\IntegrationApp;
use App\Domain\Integration\Services\LancoreIntegrationsReconciler;

beforeEach(function () {
    config()->set('integrations.domain', 'lanparty.de');
    config()->set('integrations.satellite_host_style', 'flat');
    config()->set('integrations.scheme', 'https');
});

it('writes the canonical roles webhook endpoint when no path env is set', function () {
    config()->set('integrations.apps', [
        'lanhelp' => [
            'name' => 'LanHelp',
            'send_role_updates' => true,
            'token' => 'lci_default-paths-test-aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        ],
    ]);

    app(LancoreIntegrationsReconciler::class)->reconcile();

    expect(IntegrationApp::where('slug', 'lanhelp')->firstOrFail()->roles_endpoint)
        ->toBe('https://lanhelp.lanparty.de/api/webhooks/roles');
});

it('writes the canonical announcement webhook endpoint when no path env is set', function () {
    config()->set('integrations.apps', [
        'lanshout' => [
            'name' => 'LanShout',
            'send_announcements' => true,
            'token' => 'lci_default-paths-test-aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        ],
    ]);

    app(LancoreIntegrationsReconciler::class)->reconcile();

    expect(IntegrationApp::where('slug', 'lanshout')->firstOrFail()->announcement_endpoint)
        ->toBe('https://lanshout.lanparty.de/api/webhooks/announcements');
});

it('honors an explicit roles_path override when provided', function () {
    config()->set('integrations.apps', [
        'lanentrance' => [
            'name' => 'LanEntrance',
            'send_role_updates' => true,
            'roles_path' => '/custom/roles',
            'token' => 'lci_default-paths-test-aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        ],
    ]);

    app(LancoreIntegrationsReconciler::class)->reconcile();

    expect(IntegrationApp::where('slug', 'lanentrance')->firstOrFail()->roles_endpoint)
        ->toBe('https://lanentrance.lanparty.de/custom/roles');
});

it('does not prefix webhook paths with the legacy /lancore segment', function () {
    config()->set('integrations.apps', [
        'lanbrackets' => [
            'name' => 'LanBrackets',
            'send_role_updates' => true,
            'send_announcements' => true,
            'token' => 'lci_default-paths-test-aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        ],
    ]);

    app(LancoreIntegrationsReconciler::class)->reconcile();

    $app = IntegrationApp::where('slug', 'lanbrackets')->firstOrFail();
    expect($app->roles_endpoint)->not->toContain('/lancore/');
    expect($app->announcement_endpoint)->not->toContain('/lancore/');
});

it('keeps the production config defaults aligned with the canonical satellite paths', function () {
    $config = require base_path('config/integrations.php');

    foreach ($config['apps'] as $slug => $definition) {
        $rolesPathArg = $definition['roles_path'] ?? null;
        $announcementPathArg = $definition['announcement_path'] ?? null;
        $callbackPathArg = $definition['callback_path'] ?? null;

        expect($rolesPathArg)
            ->toBe('/api/webhooks/roles', "config/integrations.php apps.{$slug}.roles_path default must match satellite route registration");

        expect($announcementPathArg)
            ->toBe('/api/webhooks/announcements', "config/integrations.php apps.{$slug}.announcement_path default must match satellite route registration");

        expect($callbackPathArg)
            ->toBe('/auth/callback', "config/integrations.php apps.{$slug}.callback_path default must match satellite SSO route registration");
    }
});
