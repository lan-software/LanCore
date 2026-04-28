<?php

namespace App\Domain\Policy\Actions;

use App\Domain\Policy\Models\Policy;

/**
 * @see docs/mil-std-498/SSS.md CAP-POL-001
 * @see docs/mil-std-498/SRS.md POL-F-004
 */
class UpdatePolicy
{
    /**
     * @param  array{policy_type_id?: int, key?: string, name?: string, description?: string|null, is_required_for_registration?: bool, sort_order?: int}  $attributes
     */
    public function execute(Policy $policy, array $attributes): Policy
    {
        $policy->fill($attributes)->save();

        return $policy->fresh();
    }
}
