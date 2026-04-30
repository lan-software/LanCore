<?php

namespace App\Domain\DataLifecycle\Enums;

use App\Contracts\PermissionEnum;

/**
 * @see docs/mil-std-498/SSS.md SEC-DL-001, CAP-DL-002, CAP-DL-005, CAP-DL-006
 * @see docs/mil-std-498/SRS.md DL-F-014, DL-F-015
 */
enum Permission: string implements PermissionEnum
{
    case RequestUserDeletion = 'request_user_deletion';
    case ForceDeleteUserData = 'force_delete_user_data';
    case ManageRetentionPolicies = 'manage_retention_policies';
    case ViewDeletionRequests = 'view_deletion_requests';
}
