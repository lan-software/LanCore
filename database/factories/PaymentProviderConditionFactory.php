<?php

namespace Database\Factories;

use App\Domain\Shop\Enums\PaymentMethod;
use App\Domain\Shop\Models\PaymentProviderCondition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentProviderCondition>
 */
class PaymentProviderConditionFactory extends Factory
{
    protected $model = PaymentProviderCondition::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'content' => fake()->optional()->paragraphs(2, true),
            'acknowledgement_label' => fake()->sentence(),
            'is_required' => true,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }

    public function forStripe(): static
    {
        return $this->state(fn (array $attributes): array => [
            'payment_method' => PaymentMethod::Stripe,
        ]);
    }

    public function forOnSite(): static
    {
        return $this->state(fn (array $attributes): array => [
            'payment_method' => PaymentMethod::OnSite,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
