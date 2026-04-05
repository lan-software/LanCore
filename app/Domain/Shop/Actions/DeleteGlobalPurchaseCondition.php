<?php

namespace App\Domain\Shop\Actions;

use App\Domain\Shop\Models\GlobalPurchaseCondition;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SRS.md SHP-F-010
 */
class DeleteGlobalPurchaseCondition
{
    public function execute(GlobalPurchaseCondition $condition): void
    {
        DB::transaction(fn () => $condition->delete());
    }
}
