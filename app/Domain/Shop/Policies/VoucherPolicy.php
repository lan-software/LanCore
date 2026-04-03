<?php

namespace App\Domain\Shop\Policies;

use App\Domain\Shop\Enums\Permission;
use App\Domain\Shop\Models\Voucher;
use App\Enums\AuditPermission;
use App\Models\User;

/**
 * @see docs/mil-std-498/SSS.md SEC-007
 * @see docs/mil-std-498/SRS.md SHP-F-014
 */
class VoucherPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageVouchers);
    }

    public function view(User $user): bool
    {
        return $user->hasPermission(Permission::ManageVouchers);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageVouchers);
    }

    public function update(User $user): bool
    {
        return $user->hasPermission(Permission::ManageVouchers);
    }

    public function delete(User $user): bool
    {
        return $user->hasPermission(Permission::ManageVouchers);
    }

    public function viewAudit(User $user, Voucher $voucher): bool
    {
        return $user->hasPermission(AuditPermission::ViewAuditLogs);
    }
}
