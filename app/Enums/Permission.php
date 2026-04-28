<?php

namespace App\Enums;

use App\Contracts\PermissionEnum;

/**
 * @see docs/mil-std-498/SRS.md USR-F-014
 */
enum Permission: string implements PermissionEnum
{
    case ManageUsers = 'manage_users';
    case SyncUserRoles = 'sync_user_roles';
    case DeleteUsers = 'delete_users';
    case ExportUserPersonalData = 'export_user_personal_data';
}
