<?php

namespace App\Domain\Theme\Enums;

use App\Contracts\PermissionEnum;

/**
 * @see docs/mil-std-498/SRS.md THM-F-003
 */
enum Permission: string implements PermissionEnum
{
    case ManageThemes = 'manage_themes';
}
