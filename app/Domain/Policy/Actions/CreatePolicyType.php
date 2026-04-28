<?php

namespace App\Domain\Policy\Actions;

use App\Domain\Policy\Models\PolicyType;

/**
 * @see docs/mil-std-498/SSS.md CAP-POL-002
 * @see docs/mil-std-498/SRS.md POL-F-006
 */
class CreatePolicyType
{
    /**
     * @param  array{key: string, label: string, description?: string|null}  $attributes
     */
    public function execute(array $attributes): PolicyType
    {
        return PolicyType::create($attributes);
    }
}
