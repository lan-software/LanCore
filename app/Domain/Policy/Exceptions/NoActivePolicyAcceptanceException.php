<?php

namespace App\Domain\Policy\Exceptions;

use App\Domain\Policy\Models\Policy;
use App\Models\User;
use RuntimeException;

class NoActivePolicyAcceptanceException extends RuntimeException
{
    public function __construct(public readonly User $user, public readonly Policy $policy)
    {
        parent::__construct(sprintf(
            'User #%d has no active acceptance to withdraw for policy "%s".',
            $user->id,
            $policy->key,
        ));
    }
}
