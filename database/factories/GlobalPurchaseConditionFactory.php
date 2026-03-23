<?php

namespace Database\Factories;

use App\Domain\Shop\Models\GlobalPurchaseCondition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GlobalPurchaseCondition>
 */
class GlobalPurchaseConditionFactory extends Factory
{
    protected $model = GlobalPurchaseCondition::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'content' => fake()->optional()->paragraphs(2, true),
            'acknowledgement_label' => fake()->sentence(),
            'is_required' => true,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }

    public function optional(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_required' => false,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
