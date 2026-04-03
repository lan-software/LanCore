<?php

namespace App\Domain\Sponsoring\Policies;

use App\Domain\Sponsoring\Enums\Permission;
use App\Domain\Sponsoring\Models\SponsorLevel;
use App\Enums\Permission as AppPermission;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md SEC-007, SPO-F-004
 */
class SponsorLevelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageSponsorLevels);
    }

    public function view(User $user, SponsorLevel $sponsorLevel): bool
    {
        return $user->hasPermission(Permission::ManageSponsorLevels);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageSponsorLevels);
    }

    public function update(User $user, SponsorLevel $sponsorLevel): bool
    {
        return $user->hasPermission(Permission::ManageSponsorLevels);
    }

    public function delete(User $user, SponsorLevel $sponsorLevel): bool
    {
        return $user->hasPermission(Permission::ManageSponsorLevels);
    }

    public function viewAudit(User $user, SponsorLevel $sponsorLevel): bool
    {
        return $user->hasPermission(AppPermission::ViewAuditLogs);
    }
}
