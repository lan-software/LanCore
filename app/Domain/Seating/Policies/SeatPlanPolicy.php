<?php

namespace App\Domain\Seating\Policies;

use App\Domain\Seating\Enums\Permission;
use App\Domain\Seating\Models\SeatPlan;
use App\Enums\AuditPermission;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md SEC-007, SET-F-004
 */
class SeatPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageSeatPlans);
    }

    public function view(User $user, SeatPlan $seatPlan): bool
    {
        return $user->hasPermission(Permission::ManageSeatPlans);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageSeatPlans);
    }

    public function update(User $user, SeatPlan $seatPlan): bool
    {
        return $user->hasPermission(Permission::ManageSeatPlans);
    }

    public function delete(User $user, SeatPlan $seatPlan): bool
    {
        return $user->hasPermission(Permission::ManageSeatPlans);
    }

    public function viewAudit(User $user, SeatPlan $seatPlan): bool
    {
        return $user->hasPermission(AuditPermission::ViewAuditLogs);
    }
}
