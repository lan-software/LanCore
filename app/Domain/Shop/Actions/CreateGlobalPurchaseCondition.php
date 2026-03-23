<?php

namespace App\Domain\Shop\Actions;

use App\Domain\Shop\Models\GlobalPurchaseCondition;
use Illuminate\Support\Facades\DB;

class CreateGlobalPurchaseCondition
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes): GlobalPurchaseCondition
    {
        return DB::transaction(fn (): GlobalPurchaseCondition => GlobalPurchaseCondition::create($attributes));
    }
}
