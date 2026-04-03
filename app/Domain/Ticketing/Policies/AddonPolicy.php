<?php

namespace App\Domain\Ticketing\Policies;

use App\Domain\Ticketing\Enums\Permission;
use App\Domain\Ticketing\Models\Addon;
use App\Enums\AuditPermission;
use App\Models\User;

/**
 * @see docs/mil-std-498/SSS.md SEC-007
 * @see docs/mil-std-498/SRS.md TKT-F-008
 */
class AddonPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageTicketing);
    }

    public function view(User $user, Addon $addon): bool
    {
        return $user->hasPermission(Permission::ManageTicketing);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageTicketing);
    }

    public function update(User $user, Addon $addon): bool
    {
        return $user->hasPermission(Permission::ManageTicketing);
    }

    public function delete(User $user, Addon $addon): bool
    {
        return $user->hasPermission(Permission::ManageTicketing);
    }

    public function viewAudit(User $user, Addon $addon): bool
    {
        return $user->hasPermission(AuditPermission::ViewAuditLogs);
    }
}
