<?php

namespace App\Domain\Announcement\Policies;

use App\Domain\Announcement\Enums\Permission;
use App\Domain\Announcement\Models\Announcement;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md SEC-007, ANN-F-005
 */
class AnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageAnnouncements);
    }

    public function view(User $user, Announcement $announcement): bool
    {
        return $user->hasPermission(Permission::ManageAnnouncements);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageAnnouncements);
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $user->hasPermission(Permission::ManageAnnouncements);
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $user->hasPermission(Permission::ManageAnnouncements);
    }
}
