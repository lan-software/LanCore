<?php

namespace App\Domain\Shop\Actions;

use App\Domain\Shop\Models\PaymentProviderCondition;
use Illuminate\Support\Facades\DB;

class UpdatePaymentProviderCondition
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(PaymentProviderCondition $condition, array $attributes): PaymentProviderCondition
    {
        return DB::transaction(function () use ($condition, $attributes): PaymentProviderCondition {
            $condition->fill($attributes)->save();

            return $condition;
        });
    }
}
