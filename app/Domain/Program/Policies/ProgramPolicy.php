<?php

namespace App\Domain\Program\Policies;

use App\Domain\Program\Models\Program;
use App\Enums\Permission;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md SEC-007
 */
class ProgramPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManagePrograms);
    }

    public function view(User $user, Program $program): bool
    {
        return $user->hasPermission(Permission::ManagePrograms);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManagePrograms);
    }

    public function update(User $user, Program $program): bool
    {
        return $user->hasPermission(Permission::ManagePrograms);
    }

    public function delete(User $user, Program $program): bool
    {
        return $user->hasPermission(Permission::ManagePrograms);
    }

    public function viewAudit(User $user, Program $program): bool
    {
        return $user->hasPermission(Permission::ViewAuditLogs);
    }
}
