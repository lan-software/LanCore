<?php

namespace App\Concerns;

use App\Contracts\PermissionEnum;
use App\Enums\RolePermissionMap;

trait HasPermissions
{
    public function hasPermission(PermissionEnum $permission): bool
    {
        foreach ($this->roles as $role) {
            if (in_array($permission, RolePermissionMap::forRole($role->name), true)) {
                return true;
            }
        }

        return false;
    }

    public function hasAnyPermission(PermissionEnum ...$permissions): bool
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
     * @return array<int, PermissionEnum>
     */
    public function allPermissions(): array
    {
        $permissions = [];

        foreach ($this->roles as $role) {
            foreach (RolePermissionMap::forRole($role->name) as $permission) {
                $permissions[$permission->value] = $permission;
            }
        }

        return array_values($permissions);
    }
}
