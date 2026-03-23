<?php

namespace Database\Factories;

use App\Domain\Shop\Models\PurchaseRequirement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseRequirement>
 */
class PurchaseRequirementFactory extends Factory
{
    protected $model = PurchaseRequirement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'requirements_content' => fake()->optional()->paragraphs(2, true),
            'acknowledgements' => [fake()->sentence()],
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
