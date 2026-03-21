<?php

namespace App\Domain\Shop\Actions;

use App\Domain\Shop\Models\Voucher;
use Illuminate\Support\Facades\DB;

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
