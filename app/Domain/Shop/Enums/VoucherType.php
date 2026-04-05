<?php

namespace App\Domain\Shop\Enums;

/**
 * @see docs/mil-std-498/SSS.md CAP-SHP-005
 * @see docs/mil-std-498/SRS.md SHP-F-007
 */
enum VoucherType: string
{
    case FixedAmount = 'fixed_amount';
    case Percentage = 'percentage';
}
