<?php

namespace App\Domain\Shop\Actions;

use App\Domain\Shop\Models\Voucher;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SSS.md CAP-SHP-005
 * @see docs/mil-std-498/SRS.md SHP-F-007
 */
class CreateVoucher
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes): Voucher
    {
        return DB::transaction(fn (): Voucher => Voucher::create($attributes));
    }
}
