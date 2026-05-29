<?php

use App\Domain\Newsletter\Actions\FetchListsFromListmonk;
use App\Domain\Newsletter\Exceptions\ListmonkException;
use App\Domain\Newsletter\Jobs\ReconcileSubscriptionsJob;
use App\Domain\Newsletter\Jobs\SubscribeUserJob;
use App\Domain\Newsletter\Models\NewsletterList;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| newsletter:reconcile-subscriptions
|--------------------------------------------------------------------------
*/

it('warns and exits with code 2 when reconcile runs while Listmonk is disabled', function (): void {
    config()->set('listmonk.enabled', false);
    Bus::fake();

    $this->artisan('newsletter:reconcile-subscriptions')
        ->expectsOutputToContain('Listmonk integration is disabled')
        ->assertExitCode(2);

    Bus::assertNothingDispatched();
});

it('queues the reconcile fan-out when Listmonk is enabled', function (): void {
    config()->set('listmonk.enabled', true);
    Bus::fake();
    NewsletterList::factory()->count(2)->create();

    $this->artisan('newsletter:reconcile-subscriptions')
        ->expectsOutputToContain('Reconcile fan-out queued.')
        ->assertSuccessful();

    Bus::assertDispatchedTimes(ReconcileSubscriptionsJob::class, 1);
});

/*
|--------------------------------------------------------------------------
| newsletter:lists:fetch
|--------------------------------------------------------------------------
*/

it('warns and exits with code 2 when fetch runs while Listmonk is disabled', function (): void {
    config()->set('listmonk.enabled', false);

    $this->artisan('newsletter:lists:fetch')
        ->expectsOutputToContain('Listmonk integration is disabled')
        ->assertExitCode(2);
});

it('reports the sync stats when fetching lists succeeds', function (): void {
    config()->set('listmonk.enabled', true);
    config()->set('listmonk.base_url', 'https://listmonk.test');
    config()->set('listmonk.username', 'admin');
    config()->set('listmonk.password', 'secret');

    NewsletterList::factory()->create(['listmonk_id' => 1, 'name' => 'Old']);
    NewsletterList::factory()->create(['listmonk_id' => 2, 'name' => 'Stale']);

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

    $this->artisan('newsletter:lists:fetch')
        ->expectsOutputToContain('1 created, 1 updated, 1 removed')
        ->assertSuccessful();
});

it('fails gracefully when the Listmonk fetch throws', function (): void {
    config()->set('listmonk.enabled', true);

    $this->mock(FetchListsFromListmonk::class)
        ->shouldReceive('execute')
        ->once()
        ->andThrow(new ListmonkException('boom', ListmonkException::KIND_UNREACHABLE));

    $this->artisan('newsletter:lists:fetch')
        ->expectsOutputToContain('Listmonk fetch failed [unreachable]')
        ->assertFailed();
});

/*
|--------------------------------------------------------------------------
| newsletter:opt-in-all
|--------------------------------------------------------------------------
*/

it('fails when the target newsletter list does not exist', function (): void {
    $this->artisan('newsletter:opt-in-all', ['list' => 99999])
        ->expectsOutputToContain('Newsletter list #99999 not found.')
        ->assertFailed();
});

it('queues one subscribe job per verified user for the given list', function (): void {
    Bus::fake();

    User::factory()->count(3)->create(['email_verified_at' => now()]);
    User::factory()->create(['email_verified_at' => null]);

    $list = NewsletterList::factory()->create(['name' => 'Announcements']);

    $this->artisan('newsletter:opt-in-all', ['list' => $list->id])
        ->expectsOutputToContain("Queued opt-in jobs for 3 verified users into list 'Announcements'.")
        ->assertSuccessful();

    Bus::assertDispatchedTimes(SubscribeUserJob::class, 3);
});
