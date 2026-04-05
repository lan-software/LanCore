<?php

namespace App\Domain\Event\Enums;

use App\Contracts\PermissionEnum;

enum Permission: string implements PermissionEnum
{
    case ManageEvents = 'manage_events';
}
