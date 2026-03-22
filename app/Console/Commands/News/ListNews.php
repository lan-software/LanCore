<?php

namespace App\Console\Commands\News;

use App\Domain\News\Enums\ArticleVisibility;
use App\Domain\News\Models\NewsArticle;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('news:list {--visibility= : Filter by visibility (draft, internal, public)} {--archived : Show only archived articles}')]
#[Description('List all news articles')]
class ListNews extends Command
{
    public function handle(): int
    {
        $query = NewsArticle::query()->with('author');

        if ($this->option('visibility') !== null) {
            $visibility = ArticleVisibility::tryFrom($this->option('visibility'));

            if (! $visibility) {
                $this->error("Invalid visibility '{$this->option('visibility')}'. Valid values: ".implode(', ', array_column(ArticleVisibility::cases(), 'value')));

                return self::FAILURE;
            }

            $query->where('visibility', $visibility);
        }

        if ($this->option('archived')) {
            $query->where('is_archived', true);
        }

        $articles = $query->orderBy('published_at', 'desc')->get();

        if ($articles->isEmpty()) {
            $this->info('No news articles found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Title', 'Visibility', 'Author', 'Published At', 'Archived'],
            $articles->map(fn (NewsArticle $article) => [
                $article->id,
                $article->title,
                $article->visibility->value,
                $article->author?->name ?? '-',
                $article->published_at?->format('Y-m-d H:i') ?? '-',
                $article->is_archived ? 'Yes' : 'No',
            ]),
        );

        return self::SUCCESS;
    }
}
