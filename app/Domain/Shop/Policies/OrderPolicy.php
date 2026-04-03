<?php

namespace App\Domain\Shop\Policies;

use App\Domain\Shop\Models\Order;
use App\Enums\Permission;
use App\Models\User;

/**
 * @see docs/mil-std-498/SSS.md SEC-007
 * @see docs/mil-std-498/SRS.md SHP-F-014
 */
class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ViewOrders);
    }

    public function view(User $user, Order $order): bool
    {
        return $user->hasPermission(Permission::ViewOrders) || $order->user_id === $user->id;
    }

    public function confirmPayment(User $user, Order $order): bool
    {
        return $user->hasPermission(Permission::ManageOrders);
    }
}
