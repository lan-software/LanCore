<?php

namespace App\Domain\Shop\Actions;

use App\Domain\Shop\Enums\OrderStatus;
use App\Domain\Shop\Enums\PaymentMethod;
use App\Domain\Shop\Events\TicketPurchased;
use App\Domain\Shop\Models\Order;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Addon;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketType;
use App\Models\User;
use App\Notifications\OrderConfirmationNotification;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SSS.md CAP-SHP-004
 * @see docs/mil-std-498/SRS.md SHP-F-006, SHP-F-012
 */
class FulfillOrder
{
    public function execute(Order $order): void
    {
        if ($order->status === OrderStatus::Completed) {
            return;
        }

        DB::transaction(function () use ($order): void {
            $updateData = ['status' => OrderStatus::Completed];

            // Stripe orders are paid when fulfilled; on-site orders need admin confirmation.
            if ($order->payment_method !== PaymentMethod::OnSite) {
                $updateData['paid_at'] = now();
            }

            $order->update($updateData);

            $items = json_decode($order->metadata ?? '[]', true);

            if (empty($items)) {
                return;
            }

            foreach ($items as $item) {
                $ticketType = TicketType::findOrFail($item['ticket_type_id']);

                if (! $ticketType->is_locked) {
                    $ticketType->update(['is_locked' => true]);
                }

                for ($i = 0; $i < $item['quantity']; $i++) {
                    $ticket = Ticket::create([
                        'status' => TicketStatus::Active,
                        'ticket_type_id' => $ticketType->id,
                        'event_id' => $order->event_id,
                        'order_id' => $order->id,
                        'owner_id' => $order->user_id,
                        'manager_id' => $order->user_id,
                    ]);

                    // Assign the purchaser as the first user on the ticket
                    $ticket->users()->attach($order->user_id);

                    foreach ($item['addon_ids'] ?? [] as $addonId) {
                        $addon = Addon::findOrFail($addonId);
                        $ticket->addons()->attach($addonId, [
                            'price_paid' => $addon->price,
                            'order_id' => $order->id,
                        ]);
                    }
                }
            }

            if ($order->voucher_id) {
                $order->voucher->increment('times_used');
            }
        });

        /** @var User $user */
        $user = $order->user;

        $user->notify(new OrderConfirmationNotification($order));

        TicketPurchased::dispatch($user, $order);
    }
}
