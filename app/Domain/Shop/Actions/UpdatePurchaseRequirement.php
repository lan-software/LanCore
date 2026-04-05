<?php

namespace App\Domain\Shop\Actions;

use App\Domain\Shop\Models\PurchaseRequirement;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SRS.md SHP-F-009
 */
class UpdatePurchaseRequirement
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int>  $ticketTypeIds
     * @param  array<int>  $addonIds
     */
    public function execute(PurchaseRequirement $requirement, array $attributes, array $ticketTypeIds = [], array $addonIds = []): PurchaseRequirement
    {
        return DB::transaction(function () use ($requirement, $attributes, $ticketTypeIds, $addonIds): PurchaseRequirement {
            $requirement->fill($attributes)->save();
            $requirement->ticketTypes()->sync($ticketTypeIds);
            $requirement->addons()->sync($addonIds);

            return $requirement;
        });
    }
}
