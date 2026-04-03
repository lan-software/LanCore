<?php

namespace App\Domain\Shop\Enums;

use App\Contracts\PermissionEnum;

enum Permission: string implements PermissionEnum
{
    case ViewOrders = 'view_orders';
    case ManageOrders = 'manage_orders';
    case ManageVouchers = 'manage_vouchers';
    case ManageShopConditions = 'manage_shop_conditions';
}
