<?php

namespace App\Domain\Event\Policies;

use App\Domain\Event\Enums\Permission;
use App\Domain\Event\Models\Event;
use App\Enums\Permission as AppPermission;
use App\Models\User;

/**
 * @see docs/mil-std-498/SSS.md SEC-007
 * @see docs/mil-std-498/SRS.md EVT-F-003
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

    public function publish(User $user, Event $event): bool
    {
        return $user->hasPermission(Permission::ManageEvents);
    }

    public function viewAudit(User $user, Event $event): bool
    {
        return $user->hasPermission(AppPermission::ViewAuditLogs);
    }
}
