<?php

namespace App\Domain\Achievements\Enums;

use App\Contracts\PermissionEnum;

enum Permission: string implements PermissionEnum
{
    case ManageAchievements = 'manage_achievements';
}
