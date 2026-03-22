<?php

namespace App\Domain\News\Models;

use App\Domain\News\Enums\ArticleVisibility;
use App\Models\User;
use Database\Factories\NewsArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title', 'slug', 'summary', 'content', 'tags', 'image',
    'visibility', 'is_archived', 'comments_enabled', 'comments_require_approval',
    'notify_users',
    'meta_title', 'meta_description', 'og_title', 'og_description', 'og_image',
    'author_id', 'published_at',
])]
class NewsArticle extends Model
{
    /** @use HasFactory<NewsArticleFactory> */
    use HasFactory;

    protected static function newFactory(): NewsArticleFactory
    {
        return NewsArticleFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'visibility' => ArticleVisibility::class,
            'is_archived' => 'boolean',
            'comments_enabled' => 'boolean',
            'comments_require_approval' => 'boolean',
            'notify_users' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(NewsComment::class)->orderByDesc('created_at');
    }

    public function approvedComments(): HasMany
    {
        return $this->hasMany(NewsComment::class)->where('is_approved', true)->orderByDesc('created_at');
    }

    /**
     * @param  Builder<NewsArticle>  $query
     * @return Builder<NewsArticle>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('visibility', ArticleVisibility::Public)
            ->where('is_archived', false)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * @param  Builder<NewsArticle>  $query
     * @return Builder<NewsArticle>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }
}
