<?php

namespace App\Domain\Notification\Events;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRolesChanged
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<int, RoleName>  $addedRoles
     * @param  array<int, RoleName>  $removedRoles
     */
    public function __construct(
        public readonly User $user,
        public readonly array $addedRoles = [],
        public readonly array $removedRoles = [],
    ) {}
}
