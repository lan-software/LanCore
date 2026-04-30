<?php

namespace Database\Factories;

use App\Domain\DataLifecycle\Enums\DeletionInitiator;
use App\Domain\DataLifecycle\Enums\DeletionRequestStatus;
use App\Domain\DataLifecycle\Models\DeletionRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeletionRequest>
 */
class DeletionRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'initiator' => DeletionInitiator::User,
            'requested_by_user_id' => null,
            'requested_by_admin_id' => null,
            'status' => DeletionRequestStatus::PendingEmailConfirm,
            'reason' => null,
            'email_confirmation_token' => hash('sha256', fake()->uuid()),
            'metadata' => [],
        ];
    }

    public function pendingGrace(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeletionRequestStatus::PendingGrace,
            'email_confirmed_at' => now()->subDay(),
            'scheduled_for' => now()->addDays(29),
            'email_confirmation_token' => null,
        ]);
    }
}
