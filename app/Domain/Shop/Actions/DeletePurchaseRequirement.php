<?php

namespace App\Domain\Shop\Actions;

use App\Domain\Shop\Models\PurchaseRequirement;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SRS.md SHP-F-009
 */
class DeletePurchaseRequirement
{
    public function execute(PurchaseRequirement $requirement): void
    {
        DB::transaction(fn () => $requirement->delete());
    }
}
