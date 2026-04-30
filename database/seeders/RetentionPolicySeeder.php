<?php

namespace Database\Seeders;

use App\Domain\DataLifecycle\Enums\RetentionDataClass;
use App\Domain\DataLifecycle\Models\RetentionPolicy;
use Illuminate\Database\Seeder;

/**
 * Seeds the default retention windows. Re-runnable; existing rows are
 * left untouched so admin overrides aren't clobbered.
 *
 * @see docs/mil-std-498/SRS.md DL-F-011
 */
class RetentionPolicySeeder extends Seeder
{
    public function run(): void
    {
        foreach (RetentionDataClass::cases() as $class) {
            RetentionPolicy::firstOrCreate(
                ['data_class' => $class->value],
                [
                    'retention_days' => $class->defaultRetentionDays(),
                    'legal_basis' => $class->defaultLegalBasis(),
                    'description' => $class->defaultDescription(),
                    'can_be_force_deleted' => true,
                ],
            );
        }
    }
}
