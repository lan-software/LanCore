<?php

namespace App\Domain\Shop\Actions;

use App\Domain\Shop\Models\Voucher;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SRS.md SHP-F-007
 */
class DeleteVoucher
{
    public function execute(Voucher $voucher): void
    {
        DB::transaction(fn () => $voucher->delete());
    }
}
