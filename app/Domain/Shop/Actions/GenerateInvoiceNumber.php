<?php

namespace App\Domain\Shop\Actions;

use App\Domain\Shop\Models\ShopSetting;

class GenerateInvoiceNumber
{
    public function execute(): string
    {
        $prefix = ShopSetting::get('invoice_prefix', 'INV-');
        $year = now()->format('Y');
        $counterKey = "invoice_counter_{$year}";

        $counter = (int) ShopSetting::get($counterKey, 0) + 1;
        ShopSetting::set($counterKey, $counter);

        return $prefix . $year . '-' . str_pad((string) $counter, 5, '0', STR_PAD_LEFT);
    }
}
