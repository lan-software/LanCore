<?php

namespace Database\Factories;

use App\Domain\Sponsoring\Models\Sponsor;
use App\Domain\Sponsoring\Models\SponsorLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sponsor>
 */
class SponsorFactory extends Factory
{
    protected $model = Sponsor::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'description' => fake()->sentence(),
            'link' => fake()->url(),
            'logo' => null,
            'sponsor_level_id' => null,
        ];
    }

    public function withLevel(): static
    {
        return $this->state(fn (array $attributes): array => [
            'sponsor_level_id' => SponsorLevel::factory(),
        ]);
    }
}
