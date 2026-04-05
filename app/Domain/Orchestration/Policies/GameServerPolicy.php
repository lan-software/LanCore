<?php

namespace App\Domain\Orchestration\Policies;

use App\Domain\Orchestration\Enums\Permission;
use App\Domain\Orchestration\Models\GameServer;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md ORC-F-003
 */
class GameServerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageGameServers);
    }

    public function view(User $user, GameServer $server): bool
    {
        return $user->hasPermission(Permission::ManageGameServers);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageGameServers);
    }

    public function update(User $user, GameServer $server): bool
    {
        return $user->hasPermission(Permission::ManageGameServers);
    }

    public function delete(User $user, GameServer $server): bool
    {
        return $user->hasPermission(Permission::ManageGameServers);
    }
}
