<?php

namespace App\Domain\Shop\Actions;

use App\Domain\Shop\Models\PaymentProviderCondition;
use Illuminate\Support\Facades\DB;

class DeletePaymentProviderCondition
{
    public function execute(PaymentProviderCondition $condition): void
    {
        DB::transaction(fn () => $condition->delete());
    }
}
