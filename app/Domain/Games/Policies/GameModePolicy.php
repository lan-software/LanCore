<?php

namespace App\Domain\Games\Policies;

use App\Domain\Games\Models\GameMode;
use App\Enums\Permission;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md SEC-007, GAM-F-003
 */
class GameModePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageGames);
    }

    public function view(User $user, GameMode $gameMode): bool
    {
        return $user->hasPermission(Permission::ManageGames);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageGames);
    }

    public function update(User $user, GameMode $gameMode): bool
    {
        return $user->hasPermission(Permission::ManageGames);
    }

    public function delete(User $user, GameMode $gameMode): bool
    {
        return $user->hasPermission(Permission::ManageGames);
    }
}
