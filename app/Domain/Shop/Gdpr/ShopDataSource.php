<?php

namespace App\Domain\Shop\Gdpr;

use App\Domain\Policy\Gdpr\Contracts\GdprDataSource;
use App\Domain\Policy\Gdpr\GdprDataSourceResult;
use App\Domain\Policy\Gdpr\GdprExportContext;
use App\Domain\Shop\Models\Cart;
use App\Domain\Shop\Models\CheckoutAcknowledgement;
use App\Domain\Shop\Models\Order;
use App\Models\User;

class ShopDataSource implements GdprDataSource
{
    public function key(): string
    {
        return 'shop';
    }

    public function label(): string
    {
        return 'Shop orders, carts, and checkout-condition acknowledgements';
    }

    public function for(User $user, GdprExportContext $context): GdprDataSourceResult
    {
        $orders = Order::query()
            ->where('user_id', $user->id)
            ->with('orderLines')
            ->orderBy('id')
            ->get()
            ->map(fn (Order $order) => array_merge(
                $order->attributesToArray(),
                ['lines' => $order->orderLines->map->attributesToArray()->all()],
            ))
            ->all();

        $carts = Cart::query()
            ->where('user_id', $user->id)
            ->with('items')
            ->orderBy('id')
            ->get()
            ->map(fn (Cart $cart) => array_merge(
                $cart->attributesToArray(),
                ['items' => $cart->items->map->attributesToArray()->all()],
            ))
            ->all();

        $acknowledgements = CheckoutAcknowledgement::query()
            ->where('user_id', $user->id)
            ->orderBy('id')
            ->get()
            ->map->attributesToArray()
            ->all();

        return new GdprDataSourceResult([
            'orders' => $orders,
            'carts' => $carts,
            'checkout_acknowledgements' => $acknowledgements,
        ]);
    }
}
