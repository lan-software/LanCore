<?php

namespace App\Domain\Ticketing\Enums;

use App\Contracts\PermissionEnum;

enum Permission: string implements PermissionEnum
{
    case ManageTicketing = 'manage_ticketing';
    case CheckInTickets = 'check_in_tickets';
}
