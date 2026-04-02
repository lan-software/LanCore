<?php

namespace App\Domain\Games\Policies;

use App\Domain\Games\Models\GameMode;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md SEC-007, GAM-F-003
 */
class GameModePolicy
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

    public function view(User $user, GameMode $gameMode): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, GameMode $gameMode): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, GameMode $gameMode): bool
    {
        return $user->isAdmin();
    }
}
