<?php

namespace App\Concerns;

use App\Enums\Permission;

trait HasPermissions
{
    public function hasPermission(Permission $permission): bool
    {
        foreach ($this->roles as $role) {
            if (in_array($permission, Permission::forRole($role->name), true)) {
                return true;
            }
        }

        return false;
    }

    public function hasAnyPermission(Permission ...$permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Collect all permissions from all of the user's roles (deduplicated).
     *
     * @return array<int, Permission>
     */
    public function allPermissions(): array
    {
        $permissions = [];

        foreach ($this->roles as $role) {
            foreach (Permission::forRole($role->name) as $permission) {
                $permissions[$permission->value] = $permission;
            }
        }

        return array_values($permissions);
    }
}
