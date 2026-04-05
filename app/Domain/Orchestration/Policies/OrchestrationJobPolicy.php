<?php

namespace App\Domain\Orchestration\Policies;

use App\Domain\Orchestration\Enums\Permission;
use App\Domain\Orchestration\Models\OrchestrationJob;
use App\Models\User;

class OrchestrationJobPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ViewOrchestration)
            || $user->hasPermission(Permission::ManageGameServers);
    }

    public function view(User $user, OrchestrationJob $job): bool
    {
        return $user->hasPermission(Permission::ViewOrchestration)
            || $user->hasPermission(Permission::ManageGameServers);
    }
}
