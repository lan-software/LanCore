<?php

namespace App\Enums;

use App\Contracts\PermissionEnum;

/**
 * @see docs/mil-std-498/SRS.md USR-F-014
 */
enum AuditPermission: string implements PermissionEnum
{
    case ViewAuditLogs = 'view_audit_logs';
}
