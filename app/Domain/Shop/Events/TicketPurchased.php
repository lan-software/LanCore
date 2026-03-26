<?php

namespace App\Domain\Shop\Events;

use App\Domain\Shop\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketPurchased
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly Order $order,
    ) {}
}
