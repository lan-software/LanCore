<?php

namespace App\Domain\Games\Policies;

use App\Domain\Games\Models\Game;
use App\Enums\Permission;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md SEC-007, GAM-F-003
 */
class GamePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageGames);
    }

    public function view(User $user, Game $game): bool
    {
        return $user->hasPermission(Permission::ManageGames);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageGames);
    }

    public function update(User $user, Game $game): bool
    {
        return $user->hasPermission(Permission::ManageGames);
    }

    public function delete(User $user, Game $game): bool
    {
        return $user->hasPermission(Permission::ManageGames);
    }
}
