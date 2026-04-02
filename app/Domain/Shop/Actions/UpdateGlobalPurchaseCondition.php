<?php

namespace App\Domain\Shop\Actions;

use App\Domain\Shop\Models\GlobalPurchaseCondition;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SRS.md SHP-F-010
 */
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
