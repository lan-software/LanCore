<?php

namespace App\Domain\Shop\Enums;

/**
 * @see docs/mil-std-498/SSS.md CAP-SHP-002, CAP-SHP-003
 * @see docs/mil-std-498/SRS.md SHP-F-003, SHP-F-004
 */
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
