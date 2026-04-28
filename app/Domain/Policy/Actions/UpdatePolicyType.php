<?php

namespace App\Domain\Policy\Actions;

use App\Domain\Policy\Models\PolicyType;

/**
 * @see docs/mil-std-498/SSS.md CAP-POL-002
 * @see docs/mil-std-498/SRS.md POL-F-006
 */
class UpdatePolicyType
{
    /**
     * @param  array{key?: string, label?: string, description?: string|null}  $attributes
     */
    public function execute(PolicyType $policyType, array $attributes): PolicyType
    {
        $policyType->fill($attributes)->save();

        return $policyType->fresh();
    }
}
