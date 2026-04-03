<?php

namespace App\Enums;

use App\Contracts\PermissionEnum;

enum Permission: string implements PermissionEnum
{
    case ManageUsers = 'manage_users';
    case SyncUserRoles = 'sync_user_roles';
    case DeleteUsers = 'delete_users';
    case ViewAuditLogs = 'view_audit_logs';
}
