<?php

namespace App\Domain\Notification\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserAttributesUpdated
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<string, mixed>  $changedAttributes
     */
    public function __construct(
        public readonly User $user,
        public readonly array $changedAttributes,
    ) {}
}
