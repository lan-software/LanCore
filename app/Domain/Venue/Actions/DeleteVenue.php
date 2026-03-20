<?php

namespace App\Domain\Venue\Actions;

use App\Domain\Venue\Models\Venue;
use Illuminate\Support\Facades\DB;

class DeleteVenue
{
    public function execute(Venue $venue): void
    {
        DB::transaction(function () use ($venue): void {
            $address = $venue->address;
            $venue->delete();
            $address->delete();
        });
    }
}
