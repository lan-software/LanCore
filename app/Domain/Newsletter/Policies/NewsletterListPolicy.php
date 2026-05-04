<?php

namespace App\Domain\Newsletter\Policies;

use App\Domain\Newsletter\Enums\Permission;
use App\Domain\Newsletter\Models\NewsletterList;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md NLT-F-001
 */
class NewsletterListPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageNewsletterLists);
    }

    public function view(User $user, NewsletterList $list): bool
    {
        return $user->hasPermission(Permission::ManageNewsletterLists);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageNewsletterLists);
    }

    public function update(User $user, NewsletterList $list): bool
    {
        return $user->hasPermission(Permission::ManageNewsletterLists);
    }

    public function delete(User $user, NewsletterList $list): bool
    {
        return $user->hasPermission(Permission::ManageNewsletterLists);
    }
}
