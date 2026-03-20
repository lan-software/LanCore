<?php

namespace App\Domain\Venue\Actions;

use App\Domain\Venue\Models\Venue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteVenue
{
    public function execute(Venue $venue): void
    {
        DB::transaction(function () use ($venue): void {
            $venue->images->each(function ($image): void {
                Storage::delete($image->path);
            });

            $address = $venue->address;
            $venue->delete();
            $address->delete();
        });
    }
}
