<?php

namespace App\Domain\Event\Policies;

use App\Domain\Event\Enums\Permission;
use App\Domain\Event\Models\Event;
use App\Enums\AuditPermission;
use App\Models\User;

/**
 * @see docs/mil-std-498/SSS.md SEC-007, CAP-DL-008
 * @see docs/mil-std-498/SRS.md EVT-F-003, DL-F-018
 */
class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageEvents);
    }

    public function view(User $user, Event $event): bool
    {
        return $user->hasPermission(Permission::ManageEvents);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageEvents);
    }

    public function update(User $user, Event $event): bool
    {
        return $user->hasPermission(Permission::ManageEvents);
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->hasPermission(Permission::ManageEvents);
    }

    public function restore(User $user, Event $event): bool
    {
        return $user->hasPermission(Permission::ManageEvents);
    }

    /**
     * Hard-deletion of events is permanently forbidden to preserve attendance,
     * accounting, and competition history.
     *
     * @see docs/mil-std-498/SSS.md CAP-DL-008
     */
    public function forceDelete(User $user, Event $event): bool
    {
        return false;
    }

    public function publish(User $user, Event $event): bool
    {
        return $user->hasPermission(Permission::ManageEvents);
    }

    public function viewAudit(User $user, Event $event): bool
    {
        return $user->hasPermission(AuditPermission::ViewAuditLogs);
    }
}
