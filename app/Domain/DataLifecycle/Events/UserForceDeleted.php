<?php

namespace App\Domain\DataLifecycle\Events;

use Illuminate\Foundation\Events\Dispatchable;

class UserForceDeleted
{
    use Dispatchable;

    public function __construct(public int $userId, public string $reason) {}
}
