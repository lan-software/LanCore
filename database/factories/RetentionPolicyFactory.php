<?php

namespace Database\Factories;

use App\Domain\DataLifecycle\Enums\RetentionDataClass;
use App\Domain\DataLifecycle\Models\RetentionPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RetentionPolicy>
 */
class RetentionPolicyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $class = fake()->randomElement(RetentionDataClass::cases());

        return [
            'data_class' => $class,
            'retention_days' => $class->defaultRetentionDays(),
            'legal_basis' => $class->defaultLegalBasis(),
            'description' => $class->defaultDescription(),
            'can_be_force_deleted' => true,
        ];
    }
}
