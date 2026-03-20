<?php

namespace App\Actions\User;

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChangeRoles
{
    /**
     * Assign a role to the user without removing existing roles.
     * Idempotent: does nothing if the user already has the role.
     */
    public function assign(User $user, RoleName $role): void
    {
        DB::transaction(function () use ($user, $role) {
            $roleModel = Role::where('name', $role->value)->firstOrFail();

            if (! $user->hasRole($role)) {
                $user->roles()->attach($roleModel);
                $user->unsetRelation('roles');
            }
        });
    }

    /**
     * Assign a role to a collection of users.
     *
     * @param  Collection<int, User>  $users
     */
    public function assignBulk(Collection $users, RoleName $role): void
    {
        DB::transaction(function () use ($users, $role) {
            $roleModel = Role::where('name', $role->value)->firstOrFail();

            $users->each(function (User $user) use ($roleModel, $role) {
                if (! $user->hasRole($role)) {
                    $user->roles()->attach($roleModel);
                    $user->unsetRelation('roles');
                }
            });
        });
    }

    /**
     * Replace all roles on the user with the given set.
     */
    public function sync(User $user, RoleName ...$roles): void
    {
        DB::transaction(function () use ($user, $roles) {
            $roleIds = Role::whereIn(
                'name',
                array_map(fn (RoleName $r) => $r->value, $roles),
            )->pluck('id');

            $user->roles()->sync($roleIds);
            $user->unsetRelation('roles');
        });
    }
}
