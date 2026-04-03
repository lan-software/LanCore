<?php

namespace App\Domain\Venue\Enums;

use App\Contracts\PermissionEnum;

enum Permission: string implements PermissionEnum
{
    case ManageVenues = 'manage_venues';
}
