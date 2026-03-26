<?php

namespace App\Domain\Shop\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CartItemAdded
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly User $user) {}
}
