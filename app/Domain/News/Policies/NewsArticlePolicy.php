<?php

namespace App\Domain\News\Policies;

use App\Domain\News\Enums\Permission;
use App\Domain\News\Models\NewsArticle;
use App\Enums\AuditPermission;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md SEC-007, NWS-F-007
 */
class NewsArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ManageNewsArticles);
    }

    public function view(User $user, NewsArticle $article): bool
    {
        return $user->hasPermission(Permission::ManageNewsArticles);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ManageNewsArticles);
    }

    public function update(User $user, NewsArticle $article): bool
    {
        return $user->hasPermission(Permission::ManageNewsArticles);
    }

    public function delete(User $user, NewsArticle $article): bool
    {
        return $user->hasPermission(Permission::ManageNewsArticles);
    }

    public function viewAudit(User $user, NewsArticle $article): bool
    {
        return $user->hasPermission(AuditPermission::ViewAuditLogs);
    }
}
