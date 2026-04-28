<?php

namespace App\Domain\News\Gdpr;

use App\Domain\News\Models\NewsComment;
use App\Domain\News\Models\NewsCommentVote;
use App\Domain\Policy\Gdpr\Contracts\GdprDataSource;
use App\Domain\Policy\Gdpr\GdprDataSourceResult;
use App\Domain\Policy\Gdpr\GdprExportContext;
use App\Models\User;

class NewsDataSource implements GdprDataSource
{
    public function key(): string
    {
        return 'news';
    }

    public function label(): string
    {
        return 'News comments and comment votes';
    }

    public function for(User $user, GdprExportContext $context): GdprDataSourceResult
    {
        $comments = NewsComment::query()
            ->where('user_id', $user->id)
            ->orderBy('id')
            ->get()
            ->map->attributesToArray()
            ->all();

        $votes = NewsCommentVote::query()
            ->where('user_id', $user->id)
            ->with('comment:id,user_id')
            ->orderBy('id')
            ->get()
            ->map(function (NewsCommentVote $vote) use ($user, $context): array {
                $row = $vote->attributesToArray();
                $authorId = $vote->comment?->user_id;

                if ($authorId !== null) {
                    $row['voted_on_author'] = $authorId === $user->id
                        ? 'subject'
                        : $context->obfuscateUser($authorId, 'comment author');
                }

                return $row;
            })
            ->all();

        return new GdprDataSourceResult([
            'comments' => $comments,
            'votes' => $votes,
        ]);
    }
}
