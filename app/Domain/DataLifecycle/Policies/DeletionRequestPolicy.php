<?php

namespace App\Domain\DataLifecycle\Policies;

use App\Domain\DataLifecycle\Enums\Permission;
use App\Domain\DataLifecycle\Models\DeletionRequest;
use App\Models\User;

class DeletionRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ViewDeletionRequests);
    }

    public function view(User $user, DeletionRequest $request): bool
    {
        return $user->getKey() === $request->user_id
            || $user->hasPermission(Permission::ViewDeletionRequests);
    }

    public function create(User $user): bool
    {
        return ! $user->isAnonymized();
    }

    public function createForOther(User $user): bool
    {
        return $user->hasPermission(Permission::RequestUserDeletion);
    }

    public function cancel(User $user, DeletionRequest $request): bool
    {
        return $user->getKey() === $request->user_id
            || $user->hasPermission(Permission::RequestUserDeletion);
    }

    public function anonymizeNow(User $user, DeletionRequest $request): bool
    {
        return $user->hasPermission(Permission::RequestUserDeletion);
    }

    public function forceDelete(User $user): bool
    {
        return $user->hasPermission(Permission::ForceDeleteUserData);
    }
}
