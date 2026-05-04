<?php

use App\Domain\Newsletter\Actions\FetchListsFromListmonk;
use App\Domain\Newsletter\Models\NewsletterList;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config()->set('listmonk.enabled', true);
    config()->set('listmonk.base_url', 'https://listmonk.test');
    config()->set('listmonk.username', 'admin');
    config()->set('listmonk.password', 'secret');
});

it('upserts remote lists and removes orphans', function (): void {
    NewsletterList::factory()->create([
        'listmonk_id' => 1,
        'name' => 'Old',
    ]);
    NewsletterList::factory()->create([
        'listmonk_id' => 2,
        'name' => 'Stale',
    ]);

    Http::fake([
        'listmonk.test/api/lists*' => Http::response([
            'data' => [
                'results' => [
                    ['id' => 1, 'name' => 'Renamed', 'type' => 'public', 'optin' => 'double'],
                    ['id' => 3, 'name' => 'Brand New', 'type' => 'private', 'optin' => 'single'],
                ],
                'total' => 2,
                'page' => 1,
                'per_page' => 100,
            ],
        ], 200),
    ]);

    $stats = app(FetchListsFromListmonk::class)->execute();

    expect($stats)->toMatchArray(['created' => 1, 'updated' => 1, 'removed' => 1])
        ->and(NewsletterList::where('listmonk_id', 1)->first()->name)->toBe('Renamed')
        ->and(NewsletterList::where('listmonk_id', 3)->first()->name)->toBe('Brand New')
        ->and(NewsletterList::where('listmonk_id', 2)->exists())->toBeFalse();
});
