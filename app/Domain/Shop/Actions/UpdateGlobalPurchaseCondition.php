<?php

namespace App\Domain\Shop\Actions;

use App\Domain\Shop\Models\GlobalPurchaseCondition;
use Illuminate\Support\Facades\DB;

class UpdateGlobalPurchaseCondition
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(GlobalPurchaseCondition $condition, array $attributes): GlobalPurchaseCondition
    {
        return DB::transaction(function () use ($condition, $attributes): GlobalPurchaseCondition {
            $condition->fill($attributes)->save();

            return $condition;
        });
    }
}
