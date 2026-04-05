<?php

namespace App\Domain\Seating\Enums;

use App\Contracts\PermissionEnum;

enum Permission: string implements PermissionEnum
{
    case ManageSeatPlans = 'manage_seat_plans';
}
