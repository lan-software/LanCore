<?php

namespace App\Domain\News\Events;

use App\Domain\News\Models\NewsArticle;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewsArticlePublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly NewsArticle $newsArticle) {}
}
