<?php

namespace App\Domain\Shop\Http\Controllers;

use App\Domain\Event\Models\Event;
use App\Domain\Shop\Actions\CreateOrder;
use App\Domain\Shop\Actions\FulfillOrder;
use App\Domain\Shop\Contracts\Purchasable;
use App\Domain\Shop\Enums\OrderStatus;
use App\Domain\Shop\Enums\PaymentMethod;
use App\Domain\Shop\Models\Cart;
use App\Domain\Shop\Models\CartItem;
use App\Domain\Shop\Models\Order;
use App\Domain\Shop\Models\Voucher;
use App\Domain\Shop\PaymentProviders\PaymentProviderManager;
use App\Domain\Ticketing\Models\Addon;
use App\Domain\Ticketing\Models\TicketType;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(
        private readonly CreateOrder $createOrder,
        private readonly FulfillOrder $fulfillOrder,
        private readonly PaymentProviderManager $providerManager,
    ) {}

    public function show(Request $request): Response
    {
        $cart = Cart::forUser($request->user());
        $cart->load(['items.purchasable', 'event']);

        $voucherInfo = null;
        if ($cart->voucher_code) {
            $voucher = Voucher::where('code', $cart->voucher_code)->first();
            if ($voucher && $voucher->isValid()) {
                $voucherInfo = [
                    'code' => $voucher->code,
                    'type' => $voucher->type->value,
                    'discount_amount' => $voucher->discount_amount,
                    'discount_percent' => $voucher->discount_percent,
                    'discount' => $voucher->calculateDiscount($cart->subtotal()),
                ];
            } else {
                $cart->update(['voucher_code' => null]);
            }
        }

        $items = $cart->items->map(function (CartItem $item) {
            $purchasable = $item->purchasable;

            return [
                'id' => $item->id,
                'purchasable_type' => $item->purchasable_type,
                'purchasable_id' => $item->purchasable_id,
                'quantity' => $item->quantity,
                'name' => $purchasable instanceof Purchasable ? $purchasable->getTitle() : 'Unknown',
                'description' => $purchasable instanceof Purchasable ? $purchasable->getDescription() : null,
                'unit_price' => $purchasable instanceof Purchasable ? $purchasable->getUnitPrice() : 0,
                'max_quantity' => $purchasable instanceof Purchasable ? $purchasable->getMaxQuantity() : 1,
                'line_total' => $item->lineTotal(),
                'is_addon' => $item->purchasable_type === Addon::class,
            ];
        });

        $subtotal = $cart->subtotal();
        $discount = $voucherInfo['discount'] ?? 0;
        $total = max(0, $subtotal - $discount);
        $dependencyErrors = $cart->validateDependencies();

        return Inertia::render('cart/Index', [
            'cartItems' => $items,
            'event' => $cart->event,
            'voucher' => $voucherInfo,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'dependencyErrors' => $dependencyErrors,
            'paymentMethods' => $this->providerManager->availableMethods(),
        ]);
    }

    public function addItem(Request $request): RedirectResponse
    {
        $request->validate([
            'purchasable_type' => ['required', 'string', 'in:ticket_type,addon'],
            'purchasable_id' => ['required', 'integer'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'event_id' => ['required', 'integer', 'exists:events,id'],
        ]);

        $user = $request->user();
        $cart = Cart::forUser($user);

        // Ensure the cart is scoped to one event
        if ($cart->event_id && $cart->event_id !== (int) $request->input('event_id')) {
            $cart->items()->delete();
            $cart->update(['voucher_code' => null]);
        }
        $cart->update(['event_id' => $request->input('event_id')]);

        $type = $request->input('purchasable_type');
        $purchasableClass = match ($type) {
            'ticket_type' => TicketType::class,
            'addon' => Addon::class,
        };

        $purchasable = $purchasableClass::findOrFail($request->input('purchasable_id'));

        if (! $purchasable instanceof Purchasable || ! $purchasable->isAvailableForPurchase()) {
            return back()->withErrors(['cart' => "'{$purchasable->name}' is not available for purchase."]);
        }

        $quantity = min($request->input('quantity', 1), $purchasable->getMaxQuantity());

        $existingItem = $cart->items()
            ->where('purchasable_type', $purchasableClass)
            ->where('purchasable_id', $purchasable->getPurchasableId())
            ->first();

        if ($existingItem) {
            $newQty = min($existingItem->quantity + $quantity, $purchasable->getMaxQuantity());
            $existingItem->update(['quantity' => $newQty]);
        } else {
            $cart->items()->create([
                'purchasable_type' => $purchasableClass,
                'purchasable_id' => $purchasable->getPurchasableId(),
                'quantity' => $quantity,
            ]);
        }

        return back();
    }

    public function updateItem(Request $request, CartItem $cartItem): RedirectResponse
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cart = Cart::forUser($request->user());

        if ($cartItem->cart_id !== $cart->id) {
            abort(403);
        }

        $purchasable = $cartItem->purchasable;
        $maxQty = $purchasable instanceof Purchasable ? $purchasable->getMaxQuantity() : 1;
        $quantity = min($request->input('quantity'), $maxQty);

        $cartItem->update(['quantity' => $quantity]);

        return back();
    }

    public function removeItem(Request $request, CartItem $cartItem): RedirectResponse
    {
        $cart = Cart::forUser($request->user());

        if ($cartItem->cart_id !== $cart->id) {
            abort(403);
        }

        $cartItem->delete();

        // Clear cart event if empty
        if ($cart->isEmpty()) {
            $cart->update(['event_id' => null, 'voucher_code' => null]);
        }

        return back();
    }

    public function applyVoucher(Request $request): RedirectResponse
    {
        $request->validate([
            'voucher_code' => ['required', 'string', 'max:50'],
        ]);

        $cart = Cart::forUser($request->user());

        if ($cart->isEmpty()) {
            return back()->withErrors(['voucher_code' => 'Your cart is empty.']);
        }

        $voucher = Voucher::where('code', $request->input('voucher_code'))->first();

        if (! $voucher || ! $voucher->isValid()) {
            return back()->withErrors(['voucher_code' => 'Invalid or expired voucher code.']);
        }

        if ($voucher->event_id && $voucher->event_id !== $cart->event_id) {
            return back()->withErrors(['voucher_code' => 'This voucher is not valid for this event.']);
        }

        $cart->update(['voucher_code' => $voucher->code]);

        return back();
    }

    public function removeVoucher(Request $request): RedirectResponse
    {
        $cart = Cart::forUser($request->user());
        $cart->update(['voucher_code' => null]);

        return back();
    }

    public function checkout(Request $request): RedirectResponse
    {
        $request->validate([
            'payment_method' => ['required', 'string', Rule::enum(PaymentMethod::class)],
        ]);

        $cart = Cart::forUser($request->user());
        $cart->load(['items.purchasable', 'event']);

        if ($cart->isEmpty() || ! $cart->event_id) {
            return redirect()->route('cart.show')->withErrors(['cart' => 'Your cart is empty.']);
        }

        $dependencyErrors = $cart->validateDependencies();
        if (! empty($dependencyErrors)) {
            return redirect()->route('cart.show')->withErrors(['cart' => $dependencyErrors[0]]);
        }

        $event = Event::findOrFail($cart->event_id);
        $paymentMethod = PaymentMethod::from($request->input('payment_method'));

        $ticketTypeItems = [];
        $addonIds = [];

        foreach ($cart->items as $item) {
            if ($item->purchasable_type === TicketType::class) {
                $ticketTypeItems[] = [
                    'ticket_type_id' => $item->purchasable_id,
                    'quantity' => $item->quantity,
                    'addon_ids' => [],
                ];
            } elseif ($item->purchasable_type === Addon::class) {
                $addonIds[] = $item->purchasable_id;
            }
        }

        // Attach addons to the first ticket type (they'll be applied to all tickets)
        if (! empty($addonIds) && ! empty($ticketTypeItems)) {
            $ticketTypeItems[0]['addon_ids'] = $addonIds;
        }

        try {
            $result = $this->createOrder->execute(
                $request->user(),
                $event,
                $ticketTypeItems,
                $paymentMethod,
                $cart->voucher_code,
            );

            // Clear the cart after successful order creation
            $cart->items()->delete();
            $cart->update(['event_id' => null, 'voucher_code' => null]);

            return $result->toResponse();
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('cart.show')->withErrors(['cart' => $e->getMessage()]);
        }
    }

    public function checkoutSuccess(Request $request, Order $order): Response|RedirectResponse
    {
        if ($order->user_id !== $request->user()->id) {
            return redirect()->route('shop.index');
        }

        if ($order->status === OrderStatus::Pending) {
            try {
                $provider = $this->providerManager->resolve($order->payment_method);
                $success = $provider->handleSuccess($order, $request->query());

                if ($success) {
                    $this->fulfillOrder->execute($order);
                }
            } catch (\Exception) {
                // Will be handled by webhook if this fails
            }
        }

        return Inertia::render('shop/CheckoutSuccess', [
            'order' => $order->load(['tickets.ticketType', 'tickets.addons', 'event']),
        ]);
    }

    public function checkoutCancel(Request $request, Order $order): RedirectResponse
    {
        if ($order->user_id !== $request->user()->id) {
            return redirect()->route('shop.index');
        }

        if ($order->status === OrderStatus::Pending) {
            $provider = $this->providerManager->resolve($order->payment_method);
            $provider->handleCancellation($order);

            $order->update(['status' => OrderStatus::Failed]);
        }

        return redirect()->route('cart.show');
    }

    /**
     * Get the current cart item count (for navigation badge).
     */
    public function count(Request $request): JsonResponse
    {
        $cart = Cart::forUser($request->user());

        return response()->json([
            'count' => $cart->items()->sum('quantity'),
        ]);
    }
}
