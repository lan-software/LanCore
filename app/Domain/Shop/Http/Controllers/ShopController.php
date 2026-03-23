<?php

namespace App\Domain\Shop\Http\Controllers;

use App\Domain\Event\Models\Event;
use App\Domain\Shop\Models\Cart;
use App\Domain\Shop\Models\CartItem;
use App\Domain\Shop\Models\Voucher;
use App\Domain\Ticketing\Models\Addon;
use App\Domain\Ticketing\Models\TicketType;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    public function index(): Response
    {
        $event = Event::published()
            ->upcoming()
            ->orderBy('start_date')
            ->first();

        if (! $event) {
            return Inertia::render('shop/Index', [
                'event' => null,
                'ticketTypes' => [],
                'addons' => [],
                'cartItemCount' => 0,
                'cartItems' => [],
            ]);
        }

        $ticketTypes = TicketType::where('event_id', $event->id)
            ->where('is_hidden', false)
            ->with('ticketCategory')
            ->withCount('tickets')
            ->orderBy('name')
            ->get()
            ->map(fn (TicketType $type) => [
                ...$type->toArray(),
                'is_purchasable' => $type->isAvailableForPurchase(),
                'remaining_quota' => $type->remainingQuota(),
                'unavailability_reason' => $this->getTicketTypeUnavailabilityReason($type),
            ]);

        $addons = Addon::where('event_id', $event->id)
            ->where('is_hidden', false)
            ->get()
            ->map(fn (Addon $addon) => [
                ...$addon->toArray(),
                'remaining_quota' => $addon->remainingQuota(),
                'requires_ticket' => $addon->requires_ticket,
            ]);

        $cartItemCount = 0;
        $cartItems = [];
        if (auth()->check()) {
            $cart = Cart::forUser(auth()->user());
            $cartItemCount = $cart->items()->sum('quantity');
            $cartItems = $cart->items->map(fn (CartItem $item) => [
                'purchasable_type' => $item->purchasable_type,
                'purchasable_id' => $item->purchasable_id,
                'quantity' => $item->quantity,
            ]);
        }

        return Inertia::render('shop/Index', [
            'event' => $event,
            'ticketTypes' => $ticketTypes,
            'addons' => $addons,
            'cartItemCount' => $cartItemCount,
            'cartItems' => $cartItems,
        ]);
    }

    private function getTicketTypeUnavailabilityReason(TicketType $type): ?string
    {
        $now = now();

        if ($type->purchase_from && $now->isBefore($type->purchase_from)) {
            return 'Upcoming';
        }

        if ($type->purchase_until && $now->isAfter($type->purchase_until)) {
            return 'No longer available';
        }

        if ($type->tickets_count >= $type->quota) {
            return 'Out of Stock';
        }

        return null;
    }

    public function validateVoucher(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
            'event_id' => ['required', 'integer', 'exists:events,id'],
        ]);

        $voucher = Voucher::where('code', $request->input('code'))->first();

        if (! $voucher || ! $voucher->isValid()) {
            return response()->json(['valid' => false, 'message' => 'Invalid or expired voucher code.']);
        }

        if ($voucher->event_id && $voucher->event_id !== (int) $request->input('event_id')) {
            return response()->json(['valid' => false, 'message' => 'This voucher is not valid for this event.']);
        }

        return response()->json([
            'valid' => true,
            'type' => $voucher->type->value,
            'discount_amount' => $voucher->discount_amount,
            'discount_percent' => $voucher->discount_percent,
        ]);
    }
}
