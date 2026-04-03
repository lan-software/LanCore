<?php

namespace App\Domain\News\Policies;

use App\Domain\News\Models\NewsComment;
use App\Enums\Permission;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md SEC-007, NWS-F-007
 */
class NewsCommentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ModerateNewsComments);
    }

    public function update(User $user, NewsComment $comment): bool
    {
        return $user->hasPermission(Permission::ModerateNewsComments);
    }

    public function delete(User $user, NewsComment $comment): bool
    {
        return $user->hasPermission(Permission::ModerateNewsComments);
    }

    public function approve(User $user, NewsComment $comment): bool
    {
        return $user->hasPermission(Permission::ModerateNewsComments);
    }

    public function viewAudit(User $user, NewsComment $comment): bool
    {
        return $user->hasPermission(Permission::ViewAuditLogs);
    }
}
