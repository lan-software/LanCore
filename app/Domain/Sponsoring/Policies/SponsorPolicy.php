<?php

namespace App\Domain\Sponsoring\Policies;

use App\Domain\Sponsoring\Enums\Permission;
use App\Domain\Sponsoring\Models\Sponsor;
use App\Enums\AuditPermission;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md SEC-007, SPO-F-004
 */
class SponsorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(
            Permission::ManageSponsors,
            Permission::ManageAssignedSponsors,
        );
    }

    public function view(User $user, Sponsor $sponsor): bool
    {
        if ($user->hasPermission(Permission::ManageSponsors)) {
            return true;
        }

        return $user->hasPermission(Permission::ManageAssignedSponsors)
            && $user->managedSponsors()->where('sponsor_id', $sponsor->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageSponsors);
    }

    public function update(User $user, Sponsor $sponsor): bool
    {
        if ($user->hasPermission(Permission::ManageSponsors)) {
            return true;
        }

        return $user->hasPermission(Permission::ManageAssignedSponsors)
            && $user->managedSponsors()->where('sponsor_id', $sponsor->id)->exists();
    }

    public function delete(User $user, Sponsor $sponsor): bool
    {
        return $user->hasPermission(Permission::ManageSponsors);
    }

    public function manageEvents(User $user, Sponsor $sponsor): bool
    {
        return $user->hasPermission(Permission::ManageSponsors);
    }

    public function viewAudit(User $user, Sponsor $sponsor): bool
    {
        return $user->hasPermission(AuditPermission::ViewAuditLogs);
    }
}
