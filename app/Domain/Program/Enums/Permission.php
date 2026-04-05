<?php

namespace App\Domain\Program\Enums;

use App\Contracts\PermissionEnum;

enum Permission: string implements PermissionEnum
{
    case ManagePrograms = 'manage_programs';
}
