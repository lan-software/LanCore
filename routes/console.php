<?php

use App\Domain\DataLifecycle\Jobs\ProcessDueDeletionRequestsJob;
use App\Domain\Newsletter\Jobs\ReconcileSubscriptionsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Data Lifecycle nightly schedules.
 *
 * @see docs/mil-std-498/SRS.md DL-F-007, DL-F-013, DL-F-017
 */
Schedule::job(new ProcessDueDeletionRequestsJob)
    ->dailyAt('03:00')
    ->name('data-lifecycle:process-due-deletions')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('lifecycle:purge')
    ->dailyAt('03:15')
    ->name('data-lifecycle:purge-expired')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('email-log:prune-bodies')
    ->dailyAt('03:30')
    ->name('email-log:prune-bodies')
    ->withoutOverlapping()
    ->onOneServer();

/*
 * Newsletter / Listmonk subscription reconciliation.
 *
 * Background-on-access refresh covers the active-user path; this nightly
 * fan-out catches users who never visit `/settings/email` so drift
 * (someone unsubscribes inside Listmonk) is bounded to <24h.
 *
 * @see docs/mil-std-498/SRS.md NLT-F-004
 */
Schedule::job(new ReconcileSubscriptionsJob)
    ->dailyAt('03:45')
    ->name('newsletter:reconcile-subscriptions')
    ->withoutOverlapping()
    ->onOneServer();
