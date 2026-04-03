<?php

namespace App\Domain\Webhook\Policies;

use App\Domain\Webhook\Models\Webhook;
use App\Enums\Permission;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md SEC-007, WHK-F-007
 */
class WebhookPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageWebhooks);
    }

    public function view(User $user, Webhook $webhook): bool
    {
        return $user->hasPermission(Permission::ManageWebhooks);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageWebhooks);
    }

    public function update(User $user, Webhook $webhook): bool
    {
        if ($webhook->isManaged()) {
            return false;
        }

        return $user->hasPermission(Permission::ManageWebhooks);
    }

    public function delete(User $user, Webhook $webhook): bool
    {
        if ($webhook->isManaged()) {
            return false;
        }

        return $user->hasPermission(Permission::ManageWebhooks);
    }
}
