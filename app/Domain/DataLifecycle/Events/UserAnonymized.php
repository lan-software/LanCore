<?php

namespace App\Domain\DataLifecycle\Events;

use App\Domain\DataLifecycle\Models\DeletionRequest;
use Illuminate\Foundation\Events\Dispatchable;

class UserAnonymized
{
    use Dispatchable;

    public function __construct(public DeletionRequest $request) {}
}
