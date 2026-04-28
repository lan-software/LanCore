<?php

namespace Database\Factories;

use App\Domain\Policy\Models\PolicyType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PolicyType>
 */
class PolicyTypeFactory extends Factory
{
    protected $model = PolicyType::class;

    public function definition(): array
    {
        $key = Str::slug(fake()->unique()->words(2, true), '_');

        return [
            'key' => $key,
            'label' => Str::title(str_replace('_', ' ', $key)),
            'description' => fake()->sentence(),
        ];
    }
}
