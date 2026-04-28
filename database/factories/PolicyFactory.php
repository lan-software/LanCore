<?php

namespace Database\Factories;

use App\Domain\Policy\Models\Policy;
use App\Domain\Policy\Models\PolicyType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Policy>
 */
class PolicyFactory extends Factory
{
    protected $model = Policy::class;

    public function definition(): array
    {
        $key = Str::slug(fake()->unique()->words(3, true), '_');

        return [
            'policy_type_id' => PolicyType::factory(),
            'key' => $key,
            'name' => Str::title(str_replace('_', ' ', $key)),
            'description' => fake()->sentence(),
            'is_required_for_registration' => false,
            'sort_order' => 0,
            'archived_at' => null,
        ];
    }

    public function requiredForRegistration(): static
    {
        return $this->state(fn () => ['is_required_for_registration' => true]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['archived_at' => now()]);
    }
}
