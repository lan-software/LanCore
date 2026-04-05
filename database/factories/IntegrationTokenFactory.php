<?php

namespace Database\Factories;

use App\Domain\Integration\Models\IntegrationApp;
use App\Domain\Integration\Models\IntegrationToken;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<IntegrationToken>
 */
class IntegrationTokenFactory extends Factory
{
    protected $model = IntegrationToken::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plainToken = 'lci_'.Str::random(60);

        return [
            'integration_app_id' => IntegrationApp::factory(),
            'name' => fake()->word().' Token',
            'token' => hash('sha256', $plainToken),
            'plain_text_prefix' => substr($plainToken, 0, 8),
            'expires_at' => null,
            'revoked_at' => null,
        ];
    }

    public function revoked(): static
    {
        return $this->state(['revoked_at' => now()]);
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subDay()]);
    }

    public function expiresIn(int $days): static
    {
        return $this->state(['expires_at' => now()->addDays($days)]);
    }
}
