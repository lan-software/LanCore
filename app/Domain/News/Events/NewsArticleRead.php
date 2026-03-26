<?php

namespace App\Domain\News\Events;

use App\Domain\News\Models\NewsArticle;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewsArticleRead
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly NewsArticle $newsArticle,
    ) {}
}
