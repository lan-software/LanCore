<?php

namespace App\Domain\News\Policies;

use App\Domain\News\Models\NewsArticle;
use App\Models\User;

class NewsArticlePolicy
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

    public function view(User $user, NewsArticle $article): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, NewsArticle $article): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, NewsArticle $article): bool
    {
        return $user->isAdmin();
    }

    public function viewAudit(User $user, NewsArticle $article): bool
    {
        return $user->isAdmin();
    }
}
