<?php

namespace App\Domain\Event\Policies;

use App\Domain\Event\Models\Event;
use App\Models\User;

/**
 * @see docs/mil-std-498/SSS.md SEC-007
 * @see docs/mil-std-498/SRS.md EVT-F-003
 */
class EventPolicy
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

    public function view(User $user, Event $event): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Event $event): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->isAdmin();
    }

    public function publish(User $user, Event $event): bool
    {
        return $user->isAdmin();
    }

    public function viewAudit(User $user, Event $event): bool
    {
        return $user->isAdmin();
    }
}
