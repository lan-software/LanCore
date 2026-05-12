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

/*
 * Ticket-sale notifications dispatcher.
 *
 * Scans for TicketTypes whose `purchase_from` just opened or whose
 * `purchase_until - lead_minutes` window just crossed, then fans out
 * one Notification per opted-in user. Idempotency guaranteed by the
 * `release_notified_at` / `end_notified_at` sentinels on `ticket_types`.
 *
 * @see docs/mil-std-498/SRS.md NTF-F-010
 */
Schedule::command('notifications:dispatch-ticket-sale')
    ->everyFiveMinutes()
    ->name('ticketing:dispatch-sale-notifications')
    ->withoutOverlapping()
    ->onOneServer();

/*
 * Presence transition sweeper. Active → Idle and Idle → Offline transitions
 * are passive (heartbeat TTL decay), so nothing else broadcasts them. Runs
 * every minute; broadcasts are deduped against
 * `presence:last_broadcast:user:{id}` so only actual transitions hit the bus.
 *
 * @see docs/mil-std-498/SRS.md PRS-F-015
 */
Schedule::command('presence:sweep')
    ->everyMinute()
    ->name('presence:sweep')
    ->withoutOverlapping()
    ->onOneServer();
