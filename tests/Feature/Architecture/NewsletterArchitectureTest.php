<?php

/**
 * Architectural guard: the Listmonk client is the single integration seam
 * to Listmonk. No code outside the Newsletter domain or the Orchestration
 * services namespace (which exposes the connectivity test) may use it.
 */
arch('ListmonkClient stays inside the Newsletter domain and the External API tester')
    ->expect('App\\Domain\\Newsletter\\Clients\\ListmonkClient')
    ->toOnlyBeUsedIn([
        'App\\Domain\\Newsletter',
        'App\\Domain\\Orchestration\\Services\\ExternalApiTester',
    ]);

arch('ListmonkException is exposed only via the ListmonkClient surface')
    ->expect('App\\Domain\\Newsletter\\Exceptions\\ListmonkException')
    ->toOnlyBeUsedIn([
        'App\\Domain\\Newsletter',
        'App\\Domain\\Orchestration\\Services\\ExternalApiTester',
        'App\\Console\\Commands\\Newsletter\\FetchListsCommand',
    ]);
