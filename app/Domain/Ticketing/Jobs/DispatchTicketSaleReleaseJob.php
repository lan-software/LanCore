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
 * Fans out the "ticket on sale" notification to all opted-in users for one
 * TicketType, suppressing those who already hold a ticket of that type.
 *
 * Sets `release_notified_at` regardless of branch (race protection / idempotency
 * sentinel) — see SDD §5.13.
 *
 * @see docs/mil-std-498/SRS.md NTF-F-010, NTF-F-012, NTF-F-013
 */
class DispatchTicketSaleReleaseJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly TicketType $ticketType) {}

    public function handle(): void
    {
        $type = $this->ticketType->fresh();

        if (! $type || $type->release_notified_at !== null) {
            return;
        }

        if (! $type->isAvailableForPurchase()) {
            $type->forceFill(['release_notified_at' => now()])->save();

            return;
        }

        $recipients = User::query()
            ->whereHas('notificationPreference', fn ($q) => $q
                ->where(fn ($q2) => $q2
                    ->where('mail_on_ticket_sale', true)
                    ->orWhere('push_on_ticket_sale', true)
                ),
            )
            ->whereDoesntHave(
                'ownedTickets',
                fn ($q) => $q->where('ticket_type_id', $type->id),
            )
            ->whereDoesntHave(
                'assignedTickets',
                fn ($q) => $q->where('ticket_type_id', $type->id),
            )
            ->get();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new TicketSaleNotification($type, TicketSalePhase::Release));
        }

        $type->forceFill(['release_notified_at' => now()])->save();
    }
}
