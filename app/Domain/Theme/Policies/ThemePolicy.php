<?php

namespace App\Domain\Theme\Policies;

use App\Domain\Theme\Enums\Permission;
use App\Domain\Theme\Models\Theme;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md THM-F-003
 */
class ThemePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageThemes);
    }

    public function view(User $user, Theme $theme): bool
    {
        return $user->hasPermission(Permission::ManageThemes);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageThemes);
    }

    public function update(User $user, Theme $theme): bool
    {
        return $user->hasPermission(Permission::ManageThemes);
    }

    public function delete(User $user, Theme $theme): bool
    {
        return $user->hasPermission(Permission::ManageThemes);
    }
}
