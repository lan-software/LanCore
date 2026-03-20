<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DeleteUser
{
    /**
     * Delete a single user. Throws if the actor tries to delete themselves.
     */
    public function execute(User $user, User $actor): void
    {
        if ($user->is($actor)) {
            throw new RuntimeException('You cannot delete your own account.');
        }

        DB::transaction(fn () => $user->delete());
    }

    /**
     * Delete a collection of users, silently skipping the actor's own account.
     *
     * @param  Collection<int, User>  $users
     */
    public function executeBulk(Collection $users, User $actor): int
    {
        $toDelete = $users->reject(fn (User $u) => $u->is($actor));

        DB::transaction(fn () => $toDelete->each->delete());

        return $toDelete->count();
    }
}
