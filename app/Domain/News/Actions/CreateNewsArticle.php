<?php

namespace App\Domain\News\Actions;

use App\Domain\News\Events\NewsArticlePublished;
use App\Domain\News\Models\NewsArticle;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * @see docs/mil-std-498/SSS.md CAP-NWS-001
 * @see docs/mil-std-498/SRS.md NWS-F-001
 */
class CreateNewsArticle
{
    /**
     * @param  array{title: string, summary?: string|null, content?: string|null, tags?: array<string>|null, visibility: string, is_archived?: bool, comments_enabled?: bool, comments_require_approval?: bool, notify_users?: bool, meta_title?: string|null, meta_description?: string|null, og_title?: string|null, og_description?: string|null, author_id: int, published_at?: string|null}  $attributes
     */
    public function execute(array $attributes, ?UploadedFile $image = null, ?UploadedFile $ogImage = null): NewsArticle
    {
        $attributes['slug'] = Str::slug($attributes['title']);

        $existingCount = NewsArticle::where('slug', $attributes['slug'])->count();
        if ($existingCount > 0) {
            $attributes['slug'] .= '-'.($existingCount + 1);
        }

        if ($image) {
            $attributes['image'] = $image->store('news/images');
        }

        if ($ogImage) {
            $attributes['og_image'] = $ogImage->store('news/og-images');
        }

        $article = NewsArticle::create($attributes);

        if ($article->notify_users && $article->published_at !== null) {
            NewsArticlePublished::dispatch($article);
        }

        return $article;
    }
}
