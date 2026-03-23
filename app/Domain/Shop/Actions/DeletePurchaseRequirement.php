<?php

namespace App\Domain\Shop\Actions;

use App\Domain\Shop\Models\PurchaseRequirement;
use Illuminate\Support\Facades\DB;

class DeletePurchaseRequirement
{
    public function execute(PurchaseRequirement $requirement): void
    {
        DB::transaction(fn () => $requirement->delete());
    }
}
