<?php

namespace App\Domain\Shop\Actions;

use App\Domain\Event\Models\Event;
use App\Domain\Shop\Contracts\PaymentResult;
use App\Domain\Shop\Enums\OrderStatus;
use App\Domain\Shop\Enums\PaymentMethod;
use App\Domain\Shop\Models\Order;
use App\Domain\Shop\Models\OrderLine;
use App\Domain\Shop\Models\Voucher;
use App\Domain\Shop\PaymentProviders\PaymentProviderManager;
use App\Domain\Ticketing\Models\Addon;
use App\Domain\Ticketing\Models\TicketType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateOrder
{
    public function __construct(
        private readonly PaymentProviderManager $providerManager,
    ) {}

    /**
     * @param  array<int, array{ticket_type_id: int, quantity: int, addon_ids?: int[]}>  $items
     */
    public function execute(
        User $user,
        Event $event,
        array $items,
        PaymentMethod $paymentMethod,
        ?string $voucherCode = null,
    ): PaymentResult {
        return DB::transaction(function () use ($user, $event, $items, $paymentMethod, $voucherCode): PaymentResult {
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

                $orderLineData[] = [
                    'purchasable_type' => $ticketType->getPurchasableType(),
                    'purchasable_id' => $ticketType->getPurchasableId(),
                    'description' => $ticketType->getTitle(),
                    'quantity' => $item['quantity'],
                    'unit_price' => $ticketType->getUnitPrice(),
                    'total_price' => $ticketSubtotal,
                ];

                $lineItems[] = [
                    'ticket_type_id' => $item['ticket_type_id'],
                    'quantity' => $item['quantity'],
                    'addon_ids' => $item['addon_ids'] ?? [],
                ];

                foreach ($item['addon_ids'] ?? [] as $addonId) {
                    $addon = Addon::findOrFail($addonId);
                    $addonTotal = $addon->getUnitPrice() * $item['quantity'];
                    $subtotal += $addonTotal;

                    $orderLineData[] = [
                        'purchasable_type' => $addon->getPurchasableType(),
                        'purchasable_id' => $addon->getPurchasableId(),
                        'description' => "{$addon->getTitle()} (for {$ticketType->getTitle()})",
                        'quantity' => $item['quantity'],
                        'unit_price' => $addon->getUnitPrice(),
                        'total_price' => $addonTotal,
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
                'payment_method' => $paymentMethod,
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

            // Store the items structure in the order for fulfillment
            $order->update(['metadata' => json_encode($lineItems)]);

            $provider = $this->providerManager->resolve($paymentMethod);

            return $provider->initiate($user, $order->load('orderLines'));
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
