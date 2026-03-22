<?php

use App\Domain\News\Enums\ArticleVisibility;
use App\Domain\News\Models\NewsArticle;

it('lists all news articles in a table', function () {
    $article = NewsArticle::factory()->create(['title' => 'Breaking News', 'visibility' => ArticleVisibility::Public]);

    $this->artisan('news:list')
        ->expectsTable(
            ['ID', 'Title', 'Visibility', 'Author', 'Published At', 'Archived'],
            [
                [
                    $article->id,
                    'Breaking News',
                    'public',
                    $article->author?->name ?? '-',
                    $article->published_at?->format('Y-m-d H:i') ?? '-',
                    $article->is_archived ? 'Yes' : 'No',
                ],
            ],
        )
        ->assertSuccessful();
});

it('filters articles by visibility', function () {
    NewsArticle::factory()->create(['visibility' => ArticleVisibility::Draft]);
    $public = NewsArticle::factory()->create(['visibility' => ArticleVisibility::Public]);

    $this->artisan('news:list --visibility=public')
        ->expectsOutputToContain($public->title)
        ->assertSuccessful();
});

it('shows error for invalid visibility', function () {
    $this->artisan('news:list --visibility=invalid')
        ->expectsOutputToContain("Invalid visibility 'invalid'")
        ->assertFailed();
});

it('filters archived articles', function () {
    NewsArticle::factory()->create(['is_archived' => false, 'title' => 'Active']);
    NewsArticle::factory()->create(['is_archived' => true, 'title' => 'Archived']);

    $this->artisan('news:list --archived')
        ->expectsOutputToContain('Archived')
        ->assertSuccessful();
});

it('shows message when no articles found', function () {
    $this->artisan('news:list')
        ->expectsOutputToContain('No news articles found.')
        ->assertSuccessful();
});
