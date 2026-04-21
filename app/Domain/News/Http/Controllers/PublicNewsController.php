<?php

namespace App\Domain\News\Http\Controllers;

use App\Domain\News\Enums\ArticleVisibility;
use App\Domain\News\Events\NewsArticleRead;
use App\Domain\News\Models\NewsArticle;
use App\Http\Controllers\Controller;
use App\Support\StorageRole;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SSS.md CAP-NWS-001
 * @see docs/mil-std-498/SRS.md NWS-F-001, NWS-F-006
 */
class PublicNewsController extends Controller
{
    public function show(Request $request, string $slug): Response
    {
        $article = NewsArticle::query()->where('slug', $slug)
            ->where('visibility', ArticleVisibility::Public)
            ->where('is_archived', false)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with(['author:id,name'])
            ->firstOrFail();

        $comments = [];
        if ($article->comments_enabled) {
            $commentsQuery = $article->comments()
                ->where('is_approved', true)
                ->with(['user:id,name'])
                ->withSum('votes', 'value')
                ->orderByDesc('created_at')
                ->get();

            $comments = $commentsQuery->map(function ($comment) {
                $commentData = $comment->toArray();
                $commentData['vote_score'] = (int) ($comment->votes_sum_value ?? 0);

                return $commentData;
            })->all();
        }

        if ($request->user()) {
            NewsArticleRead::dispatch($request->user(), $article);
        }

        $articleData = $article->toArray();
        $articleData['image_url'] = $article->image ? StorageRole::publicUrl($article->image) : null;
        $articleData['og_image_url'] = $article->og_image ? StorageRole::publicUrl($article->og_image) : null;

        return Inertia::render('news/Show', [
            'article' => $articleData,
            'comments' => $comments,
        ]);
    }
}
