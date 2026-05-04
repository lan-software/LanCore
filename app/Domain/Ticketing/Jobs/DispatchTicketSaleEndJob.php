<?php

namespace App\Domain\Ticketing\Jobs;

use App\Domain\Ticketing\Enums\TicketSalePhase;
use App\Domain\Ticketing\Models\TicketType;
use App\Domain\Ticketing\Notifications\TicketSaleNotification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

/**
 * Fans out the "last chance" notification to all opted-in users for one
 * TicketType. No suppression — opted-in users may want extras for
 * friends or upgrades.
 *
 * @see docs/mil-std-498/SRS.md NTF-F-010, NTF-F-013
 */
class DispatchTicketSaleEndJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly TicketType $ticketType) {}

    public function handle(): void
    {
        $type = $this->ticketType->fresh();

        if (! $type || $type->end_notified_at !== null) {
            return;
        }

        if (! $type->isAvailableForPurchase()) {
            $type->forceFill(['end_notified_at' => now()])->save();

            return;
        }

        $recipients = User::query()
            ->whereHas('notificationPreference', fn ($q) => $q
                ->where(fn ($q2) => $q2
                    ->where('mail_on_ticket_sale', true)
                    ->orWhere('push_on_ticket_sale', true)
                ),
            )
            ->get();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new TicketSaleNotification($type, TicketSalePhase::End));
        }

        $type->forceFill(['end_notified_at' => now()])->save();
    }
}
