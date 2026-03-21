<?php

namespace App\Domain\Shop\Enums;

enum VoucherType: string
{
    case FixedAmount = 'fixed_amount';
    case Percentage = 'percentage';
}
