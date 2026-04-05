<?php

namespace App\Concerns;

use App\Contracts\PermissionEnum;
use App\Enums\RolePermissionMap;

/**
 * @see docs/mil-std-498/SRS.md USR-F-016
 */
trait HasPermissions
{
    /**
     * @var array<string, true>|null
     */
    private ?array $resolvedPermissions = null;

    public function hasPermission(PermissionEnum $permission): bool
    {
        $this->resolvedPermissions ??= $this->resolvePermissions();

        return isset($this->resolvedPermissions[$permission->value]);
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

    /**
     * @return array<string, true>
     */
    private function resolvePermissions(): array
    {
        $permissions = [];

        foreach ($this->roles as $role) {
            foreach (RolePermissionMap::forRole($role->name) as $permission) {
                $permissions[$permission->value] = true;
            }
        }

        return $permissions;
    }
}
