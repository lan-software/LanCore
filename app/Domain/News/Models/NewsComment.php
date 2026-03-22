<?php

namespace App\Domain\News\Models;

use App\Models\User;
use Database\Factories\NewsCommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['news_article_id', 'user_id', 'content', 'is_approved', 'edited_at'])]
class NewsComment extends Model
{
    /** @use HasFactory<NewsCommentFactory> */
    use HasFactory;

    protected static function newFactory(): NewsCommentFactory
    {
        return NewsCommentFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
            'edited_at' => 'datetime',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(NewsArticle::class, 'news_article_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(NewsCommentVote::class);
    }

    public function voteScore(): int
    {
        return $this->votes()->sum('value');
    }
}
