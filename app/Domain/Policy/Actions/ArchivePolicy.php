<?php

namespace App\Domain\Policy\Actions;

use App\Domain\Policy\Models\Policy;

/**
 * @see docs/mil-std-498/SSS.md CAP-POL-001
 * @see docs/mil-std-498/SRS.md POL-F-005
 */
class ArchivePolicy
{
    public function execute(Policy $policy): Policy
    {
        $policy->forceFill(['archived_at' => now()])->save();

        return $policy->fresh();
    }
}
