<?php

namespace App\Console\Commands\Notification;

use App\Domain\Ticketing\Jobs\DispatchTicketSaleEndJob;
use App\Domain\Ticketing\Jobs\DispatchTicketSaleReleaseJob;
use App\Domain\Ticketing\Models\TicketType;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Scans for TicketTypes whose sale window has just opened or is about to close
 * and fans out per-user notifications. Idempotent via the
 * `release_notified_at` / `end_notified_at` sentinels.
 *
 * @see docs/mil-std-498/SDD.md §5.13
 * @see docs/mil-std-498/SRS.md NTF-F-010
 */
#[Signature('notifications:dispatch-ticket-sale')]
#[Description('Dispatch ticket-sale release/end notifications to opted-in users')]
class DispatchTicketSaleNotificationsCommand extends Command
{
    public function handle(): int
    {
        $now = now();
        $releases = 0;
        $ends = 0;

        TicketType::query()
            ->whereNotNull('purchase_from')
            ->where('notify_on_release', true)
            ->whereNull('release_notified_at')
            ->where('purchase_from', '<=', $now)
            ->whereHas('event', fn ($q) => $q->published())
            ->lazyById()
            ->each(function (TicketType $type) use (&$releases) {
                DispatchTicketSaleReleaseJob::dispatch($type);
                $releases++;
            });

        TicketType::query()
            ->whereNotNull('purchase_until')
            ->where('notify_on_end', true)
            ->whereNull('end_notified_at')
            ->where('purchase_until', '>', $now)
            ->whereHas('event', fn ($q) => $q->published())
            ->lazyById()
            ->each(function (TicketType $type) use ($now, &$ends) {
                $threshold = $type->purchase_until->subMinutes($type->notify_on_end_lead_minutes);

                if ($threshold->isAfter($now)) {
                    return;
                }

                DispatchTicketSaleEndJob::dispatch($type);
                $ends++;
            });

        $this->info("Dispatched: release={$releases} end={$ends}");

        return self::SUCCESS;
    }
}
