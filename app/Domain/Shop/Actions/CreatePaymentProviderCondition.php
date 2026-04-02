<?php

namespace App\Domain\Shop\Actions;

use App\Domain\Shop\Models\PaymentProviderCondition;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SSS.md CAP-SHP-007
 * @see docs/mil-std-498/SRS.md SHP-F-010
 */
class CreatePaymentProviderCondition
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes): PaymentProviderCondition
    {
        return DB::transaction(fn (): PaymentProviderCondition => PaymentProviderCondition::create($attributes));
    }
}
