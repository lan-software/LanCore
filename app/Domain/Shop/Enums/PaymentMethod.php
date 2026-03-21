<?php

namespace App\Domain\Shop\Enums;

enum PaymentMethod: string
{
    case Stripe = 'stripe';
    case OnSite = 'on_site';

    public function label(): string
    {
        return match ($this) {
            self::Stripe => 'Credit Card (Stripe)',
            self::OnSite => 'Pay on Site',
        };
    }
}
