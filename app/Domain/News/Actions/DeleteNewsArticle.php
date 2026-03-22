<?php

namespace App\Domain\News\Actions;

use App\Domain\News\Models\NewsArticle;
use Illuminate\Support\Facades\Storage;

class DeleteNewsArticle
{
    public function execute(NewsArticle $article): void
    {
        if ($article->image) {
            Storage::delete($article->image);
        }

        if ($article->og_image) {
            Storage::delete($article->og_image);
        }

        $article->delete();
    }
}
