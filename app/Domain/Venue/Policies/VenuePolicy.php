<?php

namespace App\Domain\Venue\Policies;

use App\Domain\Venue\Models\Venue;
use App\Enums\Permission;
use App\Models\User;

/**
 * @see docs/mil-std-498/SSS.md SEC-007
 */
class VenuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageVenues);
    }

    public function view(User $user, Venue $venue): bool
    {
        return $user->hasPermission(Permission::ManageVenues);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageVenues);
    }

    public function update(User $user, Venue $venue): bool
    {
        return $user->hasPermission(Permission::ManageVenues);
    }

    public function delete(User $user, Venue $venue): bool
    {
        return $user->hasPermission(Permission::ManageVenues);
    }
}
