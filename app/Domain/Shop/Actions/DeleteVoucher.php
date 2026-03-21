<?php

namespace App\Domain\Shop\Actions;

use App\Domain\Shop\Models\Voucher;
use Illuminate\Support\Facades\DB;

class DeleteVoucher
{
    public function execute(Voucher $voucher): void
    {
        DB::transaction(fn () => $voucher->delete());
    }
}
