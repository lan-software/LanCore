<?php

namespace App\Domain\News\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['news_comment_id', 'user_id', 'value'])]
class NewsCommentVote extends Model
{
    public function comment(): BelongsTo
    {
        return $this->belongsTo(NewsComment::class, 'news_comment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
