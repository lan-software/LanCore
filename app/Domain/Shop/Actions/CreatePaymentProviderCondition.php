<?php

namespace App\Domain\Shop\Actions;

use App\Domain\Shop\Models\PaymentProviderCondition;
use Illuminate\Support\Facades\DB;

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
