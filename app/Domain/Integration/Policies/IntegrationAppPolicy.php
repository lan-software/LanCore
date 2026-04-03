<?php

namespace App\Domain\Integration\Policies;

use App\Domain\Integration\Enums\Permission;
use App\Domain\Integration\Models\IntegrationApp;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md SEC-007, INT-F-009
 */
class IntegrationAppPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageIntegrations);
    }

    public function view(User $user, IntegrationApp $app): bool
    {
        return $user->hasPermission(Permission::ManageIntegrations);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageIntegrations);
    }

    public function update(User $user, IntegrationApp $app): bool
    {
        return $user->hasPermission(Permission::ManageIntegrations);
    }

    public function delete(User $user, IntegrationApp $app): bool
    {
        return $user->hasPermission(Permission::ManageIntegrations);
    }

    public function manageTokens(User $user, IntegrationApp $app): bool
    {
        return $user->hasPermission(Permission::ManageIntegrations);
    }
}
