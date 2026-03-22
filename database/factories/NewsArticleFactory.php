<?php

namespace Database\Factories;

use App\Domain\News\Enums\ArticleVisibility;
use App\Domain\News\Models\NewsArticle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<NewsArticle>
 */
class NewsArticleFactory extends Factory
{
    protected $model = NewsArticle::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'summary' => fake()->paragraph(),
            'content' => '<p>'.implode('</p><p>', fake()->paragraphs(3)).'</p>',
            'tags' => fake()->randomElements(['update', 'event', 'announcement', 'community', 'gaming', 'tournament'], 2),
            'image' => null,
            'visibility' => ArticleVisibility::Draft,
            'is_archived' => false,
            'comments_enabled' => true,
            'comments_require_approval' => false,
            'notify_users' => false,
            'meta_title' => null,
            'meta_description' => null,
            'og_title' => null,
            'og_description' => null,
            'og_image' => null,
            'author_id' => User::factory(),
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'visibility' => ArticleVisibility::Public,
            'published_at' => now(),
        ]);
    }

    public function internal(): static
    {
        return $this->state(fn (array $attributes): array => [
            'visibility' => ArticleVisibility::Internal,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_archived' => true,
        ]);
    }
}
