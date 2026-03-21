<?php

namespace App\Domain\Shop\Actions;

use App\Domain\Event\Models\Event;
use App\Domain\Shop\Enums\OrderStatus;
use App\Domain\Shop\Models\Order;
use App\Domain\Shop\Models\OrderLine;
use App\Domain\Shop\Models\Voucher;
use App\Domain\Ticketing\Models\Addon;
use App\Domain\Ticketing\Models\TicketType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Laravel\Cashier\Checkout;

class CreateCheckoutSession
{
    /**
     * @param  array<int, array{ticket_type_id: int, quantity: int, addon_ids?: int[]}>  $items
     */
    public function execute(User $user, Event $event, array $items, ?string $voucherCode = null): Checkout
    {
        return DB::transaction(function () use ($user, $event, $items, $voucherCode): Checkout {
            $this->validateAvailability($event, $items);

            $lineItems = [];
            $subtotal = 0;
            $orderLineData = [];

            foreach ($items as $item) {
                $ticketType = TicketType::findOrFail($item['ticket_type_id']);

                if (! $ticketType->isAvailableForPurchase()) {
                    throw new InvalidArgumentException("Ticket type '{$ticketType->getTitle()}' is not available for purchase.");
                }

                if ($ticketType->remainingQuota() < $item['quantity']) {
                    throw new InvalidArgumentException("Not enough tickets available for '{$ticketType->getTitle()}'.");
                }

                $ticketSubtotal = $ticketType->getUnitPrice() * $item['quantity'];
                $subtotal += $ticketSubtotal;

                $lineItems[] = $ticketType->toLineItemData($item['quantity']);
                $orderLineData[] = [
                    'purchasable_type' => $ticketType->getPurchasableType(),
                    'purchasable_id' => $ticketType->getPurchasableId(),
                    'quantity' => $item['quantity'],
                    'unit_price' => $ticketType->getUnitPrice(),
                    'total' => $ticketSubtotal,
                ];

                foreach ($item['addon_ids'] ?? [] as $addonId) {
                    $addon = Addon::findOrFail($addonId);
                    $addonTotal = $addon->getUnitPrice() * $item['quantity'];
                    $subtotal += $addonTotal;

                    $addonLineItem = $addon->toLineItemData($item['quantity']);
                    $addonLineItem['price_data']['product_data']['name'] = "{$addon->getTitle()} (for {$ticketType->getTitle()})";
                    $lineItems[] = $addonLineItem;

                    $orderLineData[] = [
                        'purchasable_type' => $addon->getPurchasableType(),
                        'purchasable_id' => $addon->getPurchasableId(),
                        'quantity' => $item['quantity'],
                        'unit_price' => $addon->getUnitPrice(),
                        'total' => $addonTotal,
                    ];
                }
            }

            $discount = 0;
            $voucher = null;

            if ($voucherCode) {
                $voucher = Voucher::where('code', $voucherCode)->first();

                if (! $voucher || ! $voucher->isValid()) {
                    throw new InvalidArgumentException('Invalid or expired voucher code.');
                }

                if ($voucher->event_id && $voucher->event_id !== $event->id) {
                    throw new InvalidArgumentException('This voucher is not valid for this event.');
                }

                $discount = $voucher->calculateDiscount($subtotal);
            }

            $total = max(0, $subtotal - $discount);

            $order = Order::create([
                'status' => OrderStatus::Pending,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'user_id' => $user->id,
                'event_id' => $event->id,
                'voucher_id' => $voucher?->id,
            ]);

            foreach ($orderLineData as $lineData) {
                OrderLine::create([
                    'order_id' => $order->id,
                    ...$lineData,
                ]);
            }

            return $user->checkout($lineItems, [
                'success_url' => route('cart.checkout.success', ['order' => $order->id]).'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('cart.checkout.cancel', ['order' => $order->id]),
                'metadata' => [
                    'order_id' => $order->id,
                    'items' => json_encode($items),
                ],
            ]);
        });
    }

    /**
     * @param  array<int, array{ticket_type_id: int, quantity: int, addon_ids?: int[]}>  $items
     */
    private function validateAvailability(Event $event, array $items): void
    {
        $totalSeatsNeeded = 0;

        foreach ($items as $item) {
            $ticketType = TicketType::findOrFail($item['ticket_type_id']);
            $totalSeatsNeeded += $ticketType->seats_per_ticket * $item['quantity'];

            foreach ($item['addon_ids'] ?? [] as $addonId) {
                $addon = Addon::findOrFail($addonId);
                $totalSeatsNeeded += $addon->seats_consumed * $item['quantity'];
            }
        }

        $remainingCapacity = $event->remainingSeatCapacity();

        if ($totalSeatsNeeded > $remainingCapacity) {
            throw new InvalidArgumentException('Not enough seat capacity available for this event.');
        }
    }
}
