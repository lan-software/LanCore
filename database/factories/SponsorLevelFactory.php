<?php

namespace Database\Factories;

use App\Domain\Sponsoring\Models\SponsorLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SponsorLevel>
 */
class SponsorLevelFactory extends Factory
{
    protected $model = SponsorLevel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Gold', 'Silver', 'Bronze', 'Platinum', 'Diamond']),
            'color' => fake()->hexColor(),
            'sort_order' => 0,
        ];
    }
}
