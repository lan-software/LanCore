<?php

namespace Database\Factories;

use App\Domain\News\Models\NewsArticle;
use App\Domain\News\Models\NewsComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsComment>
 */
class NewsCommentFactory extends Factory
{
    protected $model = NewsComment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'news_article_id' => NewsArticle::factory(),
            'user_id' => User::factory(),
            'content' => fake()->paragraph(),
            'is_approved' => true,
        ];
    }

    public function unapproved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_approved' => false,
        ]);
    }
}
