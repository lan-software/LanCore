<?php

namespace App\Domain\Shop\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * @see docs/mil-std-498/SRS.md SHP-F-013
 */
class CartItemAdded
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly User $user) {}
}
