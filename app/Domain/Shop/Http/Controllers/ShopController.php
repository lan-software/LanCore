<?php

namespace App\Domain\Shop\Http\Controllers;

use App\Domain\Event\Models\Event;
use App\Domain\Shop\Models\Cart;
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
            ]);

        $addons = Addon::where('event_id', $event->id)
            ->where('is_hidden', false)
            ->get()
            ->map(fn (Addon $addon) => [
                ...$addon->toArray(),
                'remaining_quota' => $addon->remainingQuota(),
            ]);

        $cartItemCount = 0;
        if (auth()->check()) {
            $cart = Cart::forUser(auth()->user());
            $cartItemCount = $cart->items()->sum('quantity');
        }

        return Inertia::render('shop/Index', [
            'event' => $event,
            'ticketTypes' => $ticketTypes,
            'addons' => $addons,
            'cartItemCount' => $cartItemCount,
        ]);
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
