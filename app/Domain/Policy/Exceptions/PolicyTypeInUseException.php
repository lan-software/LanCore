<?php

namespace App\Domain\Policy\Exceptions;

use App\Domain\Policy\Models\PolicyType;
use RuntimeException;

class PolicyTypeInUseException extends RuntimeException
{
    public static function forType(PolicyType $policyType): self
    {
        return new self("Policy type '{$policyType->key}' cannot be deleted because policies still reference it.");
    }
}
