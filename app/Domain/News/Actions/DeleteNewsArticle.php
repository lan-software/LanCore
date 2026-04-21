<?php

namespace App\Domain\News\Actions;

use App\Domain\News\Models\NewsArticle;
use App\Support\StorageRole;

/**
 * @see docs/mil-std-498/SRS.md NWS-F-001
 */
class DeleteNewsArticle
{
    public function execute(NewsArticle $article): void
    {
        $disk = StorageRole::public();

        if ($article->image) {
            $disk->delete($article->image);
        }

        if ($article->og_image) {
            $disk->delete($article->og_image);
        }

        $article->delete();
    }
}
