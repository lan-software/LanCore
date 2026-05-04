<?php

namespace Database\Factories;

use App\Domain\Theme\Models\Theme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Theme>
 */
class ThemeFactory extends Factory
{
    protected $model = Theme::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => null,
            'light_config' => null,
            'dark_config' => null,
        ];
    }

    public function withPalette(): static
    {
        return $this->state(fn (array $attributes): array => [
            'light_config' => [
                '--primary' => '#0a246a',
                '--accent' => '#f0c419',
            ],
            'dark_config' => [
                '--primary' => '#1d4ed8',
                '--accent' => '#fde047',
            ],
        ]);
    }
}
