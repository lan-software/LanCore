<?php

namespace App\Domain\Policy\Actions;

use App\Domain\Policy\Exceptions\PolicyTypeInUseException;
use App\Domain\Policy\Models\PolicyType;

/**
 * @see docs/mil-std-498/SSS.md CAP-POL-002
 * @see docs/mil-std-498/SRS.md POL-F-006
 */
class DeletePolicyType
{
    /**
     * @throws PolicyTypeInUseException When any Policy still references the type.
     */
    public function execute(PolicyType $policyType): void
    {
        if ($policyType->policies()->exists()) {
            throw PolicyTypeInUseException::forType($policyType);
        }

        $policyType->delete();
    }
}
