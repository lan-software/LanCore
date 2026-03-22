<?php

namespace App\Domain\Webhook\Policies;

use App\Domain\Webhook\Models\Webhook;
use App\Models\User;

class WebhookPolicy
{
    /**
     * Superadmin bypasses all authorization checks.
     */
    public function before(User $user): ?bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Webhook $webhook): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Webhook $webhook): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Webhook $webhook): bool
    {
        return $user->isAdmin();
    }
}
