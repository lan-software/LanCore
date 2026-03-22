<?php

namespace App\Domain\Seating\Policies;

use App\Domain\Seating\Models\SeatPlan;
use App\Models\User;

class SeatPlanPolicy
{
    /**
     * Superadmin bypasses all authorization checks.
     */
    public function before(User $user): ?bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, SeatPlan $seatPlan): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, SeatPlan $seatPlan): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, SeatPlan $seatPlan): bool
    {
        return $user->isAdmin();
    }

    public function viewAudit(User $user, SeatPlan $seatPlan): bool
    {
        return $user->isAdmin();
    }
}
