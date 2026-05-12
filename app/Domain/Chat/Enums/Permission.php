<?php

namespace App\Domain\Chat\Enums;

use App\Contracts\PermissionEnum;

/**
 * @see docs/mil-std-498/SRS.md CHT-F-002
 */
enum Permission: string implements PermissionEnum
{
    case ManageChat = 'manage_chat';
    case ModerateChat = 'moderate_chat';
}
