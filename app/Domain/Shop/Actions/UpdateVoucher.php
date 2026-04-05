<?php

namespace App\Domain\Shop\Actions;

use App\Domain\Shop\Models\Voucher;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SRS.md SHP-F-007
 */
class UpdateVoucher
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Voucher $voucher, array $attributes): Voucher
    {
        return DB::transaction(function () use ($voucher, $attributes): Voucher {
            $voucher->fill($attributes)->save();

            return $voucher;
        });
    }
}
