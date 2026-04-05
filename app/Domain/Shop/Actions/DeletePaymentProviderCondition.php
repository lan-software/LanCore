<?php

namespace App\Domain\Shop\Actions;

use App\Domain\Shop\Models\PaymentProviderCondition;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SRS.md SHP-F-010
 */
class DeletePaymentProviderCondition
{
    public function execute(PaymentProviderCondition $condition): void
    {
        DB::transaction(fn () => $condition->delete());
    }
}
