<?php

namespace App\Domain\News\Http\Controllers;

use App\Domain\News\Http\Requests\NewsCommentIndexRequest;
use App\Domain\News\Http\Requests\StoreNewsCommentRequest;
use App\Domain\News\Http\Requests\UpdateNewsCommentRequest;
use App\Domain\News\Models\NewsArticle;
use App\Domain\News\Models\NewsComment;
use App\Domain\News\Models\NewsCommentVote;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NewsCommentController extends Controller
{
    public function index(NewsCommentIndexRequest $request): Response
    {
        $this->authorize('viewAny', NewsComment::class);

        $query = NewsComment::with(['article:id,title,slug,visibility,tags', 'user:id,name']);

        if ($search = $request->validated('search')) {
            $query->where(fn ($q) => $q
                ->where('content', 'ilike', "%{$search}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'ilike', "%{$search}%")));
        }

        if ($articleId = $request->validated('article_id')) {
            $query->where('news_article_id', $articleId);
        }

        if ($request->has('is_approved')) {
            $query->where('is_approved', $request->boolean('is_approved'));
        }

        if ($visibility = $request->validated('visibility')) {
            $query->whereHas('article', fn ($q) => $q->where('visibility', $visibility));
        }

        if ($tag = $request->validated('tag')) {
            $query->whereHas('article', fn ($q) => $q->whereJsonContains('tags', $tag));
        }

        $sortColumn = $request->validated('sort') ?? 'created_at';
        $sortDirection = $request->validated('direction') ?? 'desc';
        $query->orderBy($sortColumn, $sortDirection);

        $comments = $query->paginate($request->validated('per_page') ?? 20)->withQueryString();

        $articles = NewsArticle::query()
            ->select('id', 'title')
            ->orderBy('title')
            ->get();

        $tags = NewsArticle::query()
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->sort()
            ->values();

        return Inertia::render('news/comments/Index', [
            'comments' => $comments,
            'articles' => $articles,
            'tags' => $tags,
            'filters' => $request->only(['search', 'sort', 'direction', 'article_id', 'is_approved', 'visibility', 'tag', 'per_page']),
        ]);
    }

    public function store(StoreNewsCommentRequest $request, NewsArticle $newsArticle): RedirectResponse
    {
        abort_unless($newsArticle->comments_enabled, 403);

        NewsComment::create([
            'news_article_id' => $newsArticle->id,
            'user_id' => $request->user()->id,
            'content' => $request->validated('content'),
            'is_approved' => ! $newsArticle->comments_require_approval,
        ]);

        return back();
    }

    public function update(UpdateNewsCommentRequest $request, NewsComment $newsComment): RedirectResponse
    {
        $this->authorize('update', $newsComment);

        $newsComment->update([
            'content' => $request->validated('content'),
            'edited_at' => now(),
        ]);

        return back();
    }

    public function destroy(NewsComment $newsComment): RedirectResponse
    {
        $this->authorize('delete', $newsComment);

        $newsComment->delete();

        return back();
    }

    public function approve(NewsComment $newsComment): RedirectResponse
    {
        $this->authorize('approve', $newsComment);

        $newsComment->update(['is_approved' => true]);

        return back();
    }

    public function vote(NewsComment $newsComment): RedirectResponse
    {
        $user = request()->user();
        $value = (int) request()->input('value');
        abort_unless(in_array($value, [1, -1], true), 422);

        $existingVote = NewsCommentVote::where('news_comment_id', $newsComment->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingVote) {
            if ($existingVote->value === $value) {
                $existingVote->delete();
            } else {
                $existingVote->update(['value' => $value]);
            }
        } else {
            NewsCommentVote::create([
                'news_comment_id' => $newsComment->id,
                'user_id' => $user->id,
                'value' => $value,
            ]);
        }

        return back();
    }
}
