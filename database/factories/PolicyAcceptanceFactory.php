<?php

namespace Database\Factories;

use App\Domain\Policy\Enums\PolicyAcceptanceSource;
use App\Domain\Policy\Models\PolicyAcceptance;
use App\Domain\Policy\Models\PolicyVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PolicyAcceptance>
 */
class PolicyAcceptanceFactory extends Factory
{
    protected $model = PolicyAcceptance::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'policy_version_id' => PolicyVersion::factory(),
            'accepted_at' => now(),
            'locale' => config('app.locale'),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'source' => PolicyAcceptanceSource::Registration->value,
            'withdrawn_at' => null,
            'withdrawn_reason' => null,
            'withdrawn_ip' => null,
            'withdrawn_user_agent' => null,
        ];
    }

    public function withdrawn(?string $reason = null): static
    {
        return $this->state(fn () => [
            'withdrawn_at' => now(),
            'withdrawn_reason' => $reason ?? fake()->sentence(),
            'withdrawn_ip' => fake()->ipv4(),
            'withdrawn_user_agent' => fake()->userAgent(),
        ]);
    }
}
