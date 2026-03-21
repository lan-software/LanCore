<?php

namespace App\Domain\Shop\Actions;

use App\Domain\Shop\Models\Voucher;
use Illuminate\Support\Facades\DB;

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
