<?php

namespace App\Domain\Achievements\Policies;

use App\Domain\Achievements\Models\Achievement;
use App\Enums\Permission;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md SEC-007, ACH-F-005
 */
class AchievementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageAchievements);
    }

    public function view(User $user, Achievement $achievement): bool
    {
        return $user->hasPermission(Permission::ManageAchievements);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageAchievements);
    }

    public function update(User $user, Achievement $achievement): bool
    {
        return $user->hasPermission(Permission::ManageAchievements);
    }

    public function delete(User $user, Achievement $achievement): bool
    {
        return $user->hasPermission(Permission::ManageAchievements);
    }
}
