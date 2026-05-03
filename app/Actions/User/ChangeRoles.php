<?php

namespace App\Actions\User;

use App\Domain\Notification\Events\UserRolesChanged;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use OwenIt\Auditing\Events\AuditCustom;

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
                $previous = $user->roles->pluck('name')->all();
                $user->roles()->attach($roleModel);
                $user->unsetRelation('roles');

                $this->recordRoleAudit(
                    $user,
                    previous: $previous,
                    next: $user->roles->pluck('name')->all(),
                );

                UserRolesChanged::dispatch($user, addedRoles: [$role]);
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
                    $previous = $user->roles->pluck('name')->all();
                    $user->roles()->attach($roleModel);
                    $user->unsetRelation('roles');

                    $this->recordRoleAudit(
                        $user,
                        previous: $previous,
                        next: $user->roles->pluck('name')->all(),
                    );

                    UserRolesChanged::dispatch($user, addedRoles: [$role]);
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
            $previousRoles = $user->roles->pluck('name')->all();

            $roleIds = Role::whereIn(
                'name',
                array_map(fn (RoleName $r) => $r->value, $roles),
            )->pluck('id');

            $user->roles()->sync($roleIds);
            $user->unsetRelation('roles');

            $added = array_values(array_udiff($roles, $previousRoles, fn (RoleName $a, RoleName $b) => $a->value <=> $b->value));
            $removed = array_values(array_udiff($previousRoles, $roles, fn (RoleName $a, RoleName $b) => $a->value <=> $b->value));

            if ($added || $removed) {
                $this->recordRoleAudit(
                    $user,
                    previous: $previousRoles,
                    next: $user->roles->pluck('name')->all(),
                );

                UserRolesChanged::dispatch($user, addedRoles: $added, removedRoles: $removed);
            }
        });
    }

    /**
     * Emit a custom audit row capturing the role pivot change. The Auditable
     * trait does not observe the role_user pivot, so this is the only way to
     * surface role mutations in the user's audit log.
     *
     * @param  array<int, string|RoleName>  $previous
     * @param  array<int, string|RoleName>  $next
     */
    private function recordRoleAudit(User $user, array $previous, array $next): void
    {
        $user->auditEvent = 'roles_synced';
        $user->isCustomEvent = true;
        $user->auditCustomOld = ['roles' => $this->normalizeRoleNames($previous)];
        $user->auditCustomNew = ['roles' => $this->normalizeRoleNames($next)];

        Event::dispatch(new AuditCustom($user));

        $user->isCustomEvent = false;
        $user->auditCustomOld = [];
        $user->auditCustomNew = [];
    }

    /**
     * @param  array<int, string|RoleName>  $roles
     * @return array<int, string>
     */
    private function normalizeRoleNames(array $roles): array
    {
        $names = array_map(
            fn (string|RoleName $r) => $r instanceof RoleName ? $r->value : $r,
            $roles,
        );

        sort($names);

        return $names;
    }
}
