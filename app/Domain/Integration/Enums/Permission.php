<?php

namespace App\Domain\Integration\Enums;

use App\Contracts\PermissionEnum;

enum Permission: string implements PermissionEnum
{
    case ManageIntegrations = 'manage_integrations';
}
