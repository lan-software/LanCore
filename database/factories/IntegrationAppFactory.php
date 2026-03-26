<?php

namespace Database\Factories;

use App\Domain\Integration\Models\IntegrationApp;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<IntegrationApp>
 */
class IntegrationAppFactory extends Factory
{
    protected $model = IntegrationApp::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'callback_url' => fake()->url(),
            'allowed_scopes' => ['user:read'],
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    /**
     * @param  array<string>  $scopes
     */
    public function withScopes(array $scopes): static
    {
        return $this->state(['allowed_scopes' => $scopes]);
    }
}
