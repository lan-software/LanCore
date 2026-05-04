<?php

use App\Domain\Newsletter\Jobs\ReconcileListSubscriptionsJob;
use App\Domain\Newsletter\Jobs\ReconcileSubscriptionsJob;
use App\Domain\Newsletter\Models\NewsletterList;
use Illuminate\Support\Facades\Bus;

beforeEach(function (): void {
    config()->set('listmonk.enabled', true);
});

it('short-circuits when Listmonk is disabled', function (): void {
    config()->set('listmonk.enabled', false);
    Bus::fake();

    NewsletterList::factory()->count(2)->create();

    (new ReconcileSubscriptionsJob)->handle();

    Bus::assertNothingDispatched();
});

it('dispatches one per-list job per local list', function (): void {
    Bus::fake();
    NewsletterList::factory()->count(3)->create();

    (new ReconcileSubscriptionsJob)->handle();

    Bus::assertDispatchedTimes(ReconcileListSubscriptionsJob::class, 3);
});
