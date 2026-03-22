<?php

use App\Domain\News\Models\NewsArticle;
use App\Domain\News\Models\NewsComment;

it('displays a published article', function () {
    $article = NewsArticle::factory()->published()->create();

    $this->get("/news/{$article->slug}")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('news/Show')
                ->has('article')
                ->where('article.id', $article->id)
                ->where('article.title', $article->title)
        );
});

it('returns 404 for a draft article', function () {
    $article = NewsArticle::factory()->create(['visibility' => 'draft']);

    $this->get("/news/{$article->slug}")
        ->assertNotFound();
});

it('returns 404 for an internal article on public page', function () {
    $article = NewsArticle::factory()->internal()->create();

    $this->get("/news/{$article->slug}")
        ->assertNotFound();
});

it('returns 404 for an archived article', function () {
    $article = NewsArticle::factory()->published()->archived()->create();

    $this->get("/news/{$article->slug}")
        ->assertNotFound();
});

it('returns 404 for an article with future published_at date', function () {
    $article = NewsArticle::factory()->create([
        'visibility' => 'public',
        'published_at' => now()->addDay(),
    ]);

    $this->get("/news/{$article->slug}")
        ->assertNotFound();
});

it('returns 404 for a non-existent slug', function () {
    $this->get('/news/non-existent-slug')
        ->assertNotFound();
});

it('includes only approved comments in the response', function () {
    $article = NewsArticle::factory()->published()->create([
        'comments_enabled' => true,
    ]);

    $approved = NewsComment::factory()->create([
        'news_article_id' => $article->id,
        'is_approved' => true,
        'content' => 'Visible comment',
    ]);

    $unapproved = NewsComment::factory()->create([
        'news_article_id' => $article->id,
        'is_approved' => false,
        'content' => 'Hidden comment',
    ]);

    $this->get("/news/{$article->slug}")
        ->assertSuccessful()
        ->assertInertia(function ($page) {
            $page->has('comments', 1);
        });
});

it('returns empty comments when comments are disabled', function () {
    $article = NewsArticle::factory()->published()->create([
        'comments_enabled' => false,
    ]);

    NewsComment::factory()->create([
        'news_article_id' => $article->id,
        'is_approved' => true,
    ]);

    $this->get("/news/{$article->slug}")
        ->assertSuccessful()
        ->assertInertia(function ($page) {
            $page->where('comments', []);
        });
});

it('shows news section on the welcome page', function () {
    $article = NewsArticle::factory()->published()->create();

    $this->get('/')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('latestNews'));
});
