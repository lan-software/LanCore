<?php

namespace App\Domain\Shop\Actions;

use App\Domain\Shop\Models\PurchaseRequirement;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SSS.md CAP-SHP-006
 * @see docs/mil-std-498/SRS.md SHP-F-009
 */
class CreatePurchaseRequirement
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int>  $ticketTypeIds
     * @param  array<int>  $addonIds
     */
    public function execute(array $attributes, array $ticketTypeIds = [], array $addonIds = []): PurchaseRequirement
    {
        return DB::transaction(function () use ($attributes, $ticketTypeIds, $addonIds): PurchaseRequirement {
            $requirement = PurchaseRequirement::create($attributes);

            if (! empty($ticketTypeIds)) {
                $requirement->ticketTypes()->attach($ticketTypeIds);
            }

            if (! empty($addonIds)) {
                $requirement->addons()->attach($addonIds);
            }

            return $requirement;
        });
    }
}
