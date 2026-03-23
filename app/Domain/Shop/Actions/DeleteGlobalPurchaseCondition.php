<?php

namespace App\Domain\Shop\Actions;

use App\Domain\Shop\Models\GlobalPurchaseCondition;
use Illuminate\Support\Facades\DB;

class DeleteGlobalPurchaseCondition
{
    public function execute(GlobalPurchaseCondition $condition): void
    {
        DB::transaction(fn () => $condition->delete());
    }
}
