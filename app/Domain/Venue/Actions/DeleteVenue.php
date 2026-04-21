<?php

namespace App\Domain\Venue\Actions;

use App\Domain\Venue\Models\Venue;
use App\Support\StorageRole;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SRS.md EVT-F-006
 */
class DeleteVenue
{
    public function execute(Venue $venue): void
    {
        DB::transaction(function () use ($venue): void {
            $venue->images->each(function ($image): void {
                StorageRole::public()->delete($image->path);
            });

            $address = $venue->address;
            $venue->delete();
            $address->delete();
        });
    }
}
