<?php

namespace App\Domain\News\Actions;

use App\Domain\News\Events\NewsArticlePublished;
use App\Domain\News\Models\NewsArticle;
use App\Support\StorageRole;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * @see docs/mil-std-498/SRS.md NWS-F-001, NWS-F-002
 */
class UpdateNewsArticle
{
    /**
     * @param  array{title?: string, summary?: string|null, content?: string|null, tags?: array<string>|null, visibility?: string, is_archived?: bool, comments_enabled?: bool, comments_require_approval?: bool, notify_users?: bool, meta_title?: string|null, meta_description?: string|null, og_title?: string|null, og_description?: string|null, published_at?: string|null}  $attributes
     */
    public function execute(NewsArticle $article, array $attributes, ?UploadedFile $image = null, ?UploadedFile $ogImage = null, bool $removeImage = false, bool $removeOgImage = false): void
    {
        $wasPublished = $article->published_at !== null;
        $hadNotifyUsers = $article->notify_users;

        if (isset($attributes['title']) && $attributes['title'] !== $article->title) {
            $slug = Str::slug($attributes['title']);
            $existingCount = NewsArticle::where('slug', $slug)->where('id', '!=', $article->id)->count();
            if ($existingCount > 0) {
                $slug .= '-'.($existingCount + 1);
            }
            $attributes['slug'] = $slug;
        }

        $publicDisk = StorageRole::public();
        $publicDiskName = StorageRole::publicDiskName();

        if ($removeImage && $article->image) {
            $publicDisk->delete($article->image);
            $attributes['image'] = null;
        } elseif ($image) {
            if ($article->image) {
                $publicDisk->delete($article->image);
            }
            $attributes['image'] = $image->store('news/images', $publicDiskName);
        }

        if ($removeOgImage && $article->og_image) {
            $publicDisk->delete($article->og_image);
            $attributes['og_image'] = null;
        } elseif ($ogImage) {
            if ($article->og_image) {
                $publicDisk->delete($article->og_image);
            }
            $attributes['og_image'] = $ogImage->store('news/og-images', $publicDiskName);
        }

        $article->fill($attributes)->save();

        $nowPublished = $article->published_at !== null;
        $nowNotifyUsers = $article->notify_users;

        if ($nowNotifyUsers && $nowPublished && (! $wasPublished || ! $hadNotifyUsers)) {
            NewsArticlePublished::dispatch($article);
        }
    }
}
