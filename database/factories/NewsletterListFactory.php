<?php

namespace Database\Factories;

use App\Domain\Newsletter\Models\NewsletterList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsletterList>
 */
class NewsletterListFactory extends Factory
{
    protected $model = NewsletterList::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'listmonk_id' => fake()->unique()->numberBetween(1000, 99_999),
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'type' => 'private',
            'optin' => 'single',
            'tags' => [],
            'is_user_selectable' => false,
            'is_default_public' => false,
            'last_synced_at' => now(),
        ];
    }

    public function userSelectable(): static
    {
        return $this->state(fn (): array => ['is_user_selectable' => true]);
    }

    public function defaultPublic(): static
    {
        return $this->state(fn (): array => [
            'is_user_selectable' => true,
            'is_default_public' => true,
            'type' => 'public',
        ]);
    }
}
