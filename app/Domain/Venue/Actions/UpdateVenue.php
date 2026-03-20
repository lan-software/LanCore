<?php

namespace App\Domain\Venue\Actions;

use App\Domain\Venue\Models\Venue;
use App\Domain\Venue\Models\VenueImage;
use Illuminate\Support\Facades\DB;

class UpdateVenue
{
    /**
     * @param  array{name: string, description?: string|null, street: string, city: string, zip_code: string, state?: string|null, country: string}  $attributes
     * @param  array<int, array{path: string, alt_text?: string|null}>  $images
     */
    public function execute(Venue $venue, array $attributes, array $images = []): void
    {
        DB::transaction(function () use ($venue, $attributes, $images): void {
            $venue->address->fill([
                'street' => $attributes['street'],
                'city' => $attributes['city'],
                'zip_code' => $attributes['zip_code'],
                'state' => $attributes['state'] ?? null,
                'country' => $attributes['country'],
            ])->save();

            $venue->fill([
                'name' => $attributes['name'],
                'description' => $attributes['description'] ?? null,
            ])->save();

            $venue->images()->delete();

            foreach ($images as $index => $image) {
                VenueImage::create([
                    'venue_id' => $venue->id,
                    'path' => $image['path'],
                    'alt_text' => $image['alt_text'] ?? null,
                    'sort_order' => $index,
                ]);
            }
        });
    }
}
