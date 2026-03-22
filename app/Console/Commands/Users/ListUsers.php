<?php

namespace App\Console\Commands\Users;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('users:list {--role= : Filter users by role name (e.g. admin, superadmin, user, sponsor_manager)}')]
#[Description('List all registered users')]
class ListUsers extends Command
{
    public function handle(): int
    {
        $roleFilter = $this->option('role');

        $query = User::query()->with('roles');

        if ($roleFilter !== null) {
            $roleName = RoleName::tryFrom($roleFilter);

            if (! $roleName) {
                $this->error("Invalid role '{$roleFilter}'. Valid roles: ".implode(', ', array_column(RoleName::cases(), 'value')));

                return self::FAILURE;
            }

            $query->whereHas('roles', fn ($q) => $q->where('name', $roleName->value));
        }

        $users = $query->orderBy('id')->get();

        if ($users->isEmpty()) {
            $this->info('No users found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Email', 'Roles', 'Verified'],
            $users->map(fn (User $user) => [
                $user->id,
                $user->name,
                $user->email,
                $user->roles->map(fn ($role) => $role->name->value)->implode(', '),
                $user->email_verified_at ? 'Yes' : 'No',
            ]),
        );

        return self::SUCCESS;
    }
}
