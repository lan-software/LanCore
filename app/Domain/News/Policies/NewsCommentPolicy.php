<?php

namespace App\Domain\News\Policies;

use App\Domain\News\Models\NewsComment;
use App\Models\User;

class NewsCommentPolicy
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

    public function update(User $user, NewsComment $comment): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, NewsComment $comment): bool
    {
        return $user->isAdmin();
    }

    public function approve(User $user, NewsComment $comment): bool
    {
        return $user->isAdmin();
    }

    public function viewAudit(User $user, NewsComment $comment): bool
    {
        return $user->isAdmin();
    }
}
