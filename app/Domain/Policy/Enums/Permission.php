<?php

namespace App\Domain\Policy\Enums;

use App\Contracts\PermissionEnum;

enum Permission: string implements PermissionEnum
{
    case ManagePolicies = 'manage_policies';
}
