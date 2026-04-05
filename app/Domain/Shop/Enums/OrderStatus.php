<?php

namespace App\Domain\Shop\Enums;

/**
 * @see docs/mil-std-498/SSS.md CAP-SHP-004
 * @see docs/mil-std-498/SRS.md SHP-F-006
 */
enum OrderStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
    case Refunded = 'refunded';
}
