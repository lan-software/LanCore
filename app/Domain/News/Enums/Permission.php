<?php

namespace App\Domain\News\Enums;

use App\Contracts\PermissionEnum;

enum Permission: string implements PermissionEnum
{
    case ManageNewsArticles = 'manage_news_articles';
    case ModerateNewsComments = 'moderate_news_comments';
}
