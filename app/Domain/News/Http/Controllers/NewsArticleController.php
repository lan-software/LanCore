<?php

namespace App\Domain\News\Http\Controllers;

use App\Domain\News\Actions\CreateNewsArticle;
use App\Domain\News\Actions\DeleteNewsArticle;
use App\Domain\News\Actions\UpdateNewsArticle;
use App\Domain\News\Http\Requests\NewsArticleIndexRequest;
use App\Domain\News\Http\Requests\StoreNewsArticleRequest;
use App\Domain\News\Http\Requests\UpdateNewsArticleRequest;
use App\Domain\News\Models\NewsArticle;
use App\Http\Controllers\Controller;
use App\Support\StorageRole;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @see docs/mil-std-498/SSS.md CAP-NWS-001, CAP-NWS-002
 * @see docs/mil-std-498/SRS.md NWS-F-001, NWS-F-002, NWS-F-007, NWS-F-008
 */
class NewsArticleController extends Controller
{
    public function __construct(
        private readonly CreateNewsArticle $createNewsArticle,
        private readonly UpdateNewsArticle $updateNewsArticle,
        private readonly DeleteNewsArticle $deleteNewsArticle,
    ) {}

    public function index(NewsArticleIndexRequest $request): Response
    {
        $this->authorize('viewAny', NewsArticle::class);

        $query = NewsArticle::with('author:id,name');

        if ($search = $request->validated('search')) {
            $query->where(fn ($q) => $q
                ->whereLike('title', "%{$search}%")
                ->orWhereLike('summary', "%{$search}%"));
        }

        if ($visibility = $request->validated('visibility')) {
            $query->where('visibility', $visibility);
        }

        $sortColumn = $request->validated('sort') ?? 'created_at';
        $sortDirection = $request->validated('direction') ?? 'desc';
        $query->orderBy($sortColumn, $sortDirection);

        $articles = $query->paginate($request->validated('per_page') ?? 20)->withQueryString();

        return Inertia::render('news/Index', [
            'articles' => $articles,
            'filters' => $request->only(['search', 'sort', 'direction', 'visibility', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', NewsArticle::class);

        return Inertia::render('news/Create');
    }

    public function store(StoreNewsArticleRequest $request): RedirectResponse
    {
        $this->authorize('create', NewsArticle::class);

        $attributes = $request->safe()->except(['image', 'og_image', 'publish_now']);
        $attributes['author_id'] = $request->user()->id;
        $attributes['is_archived'] = $request->boolean('is_archived');
        $attributes['comments_enabled'] = $request->boolean('comments_enabled');
        $attributes['comments_require_approval'] = $request->boolean('comments_require_approval');
        $attributes['notify_users'] = $request->boolean('notify_users');

        if ($request->boolean('publish_now')) {
            $attributes['published_at'] = now();
        }

        $this->createNewsArticle->execute(
            $attributes,
            $request->file('image'),
            $request->file('og_image'),
        );

        return redirect()->route('news.index');
    }

    public function edit(NewsArticle $newsArticle): Response
    {
        $this->authorize('update', $newsArticle);

        $newsArticle->load(['author:id,name', 'comments.user:id,name']);

        $articleData = $newsArticle->toArray();
        $articleData['image_url'] = $newsArticle->image ? StorageRole::publicUrl($newsArticle->image) : null;
        $articleData['og_image_url'] = $newsArticle->og_image ? StorageRole::publicUrl($newsArticle->og_image) : null;

        return Inertia::render('news/Edit', [
            'article' => $articleData,
        ]);
    }

    public function update(UpdateNewsArticleRequest $request, NewsArticle $newsArticle): RedirectResponse
    {
        $this->authorize('update', $newsArticle);

        $attributes = $request->safe()->except(['image', 'og_image', 'remove_image', 'remove_og_image', 'publish_now']);
        $attributes['is_archived'] = $request->boolean('is_archived');
        $attributes['comments_enabled'] = $request->boolean('comments_enabled');
        $attributes['comments_require_approval'] = $request->boolean('comments_require_approval');
        $attributes['notify_users'] = $request->boolean('notify_users');

        if ($request->boolean('publish_now')) {
            $attributes['published_at'] = now();
        }

        $this->updateNewsArticle->execute(
            $newsArticle,
            $attributes,
            $request->file('image'),
            $request->file('og_image'),
            $request->boolean('remove_image'),
            $request->boolean('remove_og_image'),
        );

        return back();
    }

    public function destroy(NewsArticle $newsArticle): RedirectResponse
    {
        $this->authorize('delete', $newsArticle);

        $this->deleteNewsArticle->execute($newsArticle);

        return redirect()->route('news.index');
    }
}
