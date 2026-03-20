<?php

namespace App\Domain\Venue\Actions;

use App\Domain\Venue\Models\Address;
use App\Domain\Venue\Models\Venue;
use App\Domain\Venue\Models\VenueImage;
use Illuminate\Support\Facades\DB;

class CreateVenue
{
    /**
     * @param  array{name: string, description?: string|null, street: string, city: string, zip_code: string, state?: string|null, country: string}  $attributes
     * @param  array<int, array{path: string, alt_text?: string|null}>  $images
     */
    public function execute(array $attributes, array $images = []): Venue
    {
        return DB::transaction(function () use ($attributes, $images): Venue {
            $address = Address::create([
                'street' => $attributes['street'],
                'city' => $attributes['city'],
                'zip_code' => $attributes['zip_code'],
                'state' => $attributes['state'] ?? null,
                'country' => $attributes['country'],
            ]);

            $venue = Venue::create([
                'name' => $attributes['name'],
                'description' => $attributes['description'] ?? null,
                'address_id' => $address->id,
            ]);

            foreach ($images as $index => $image) {
                VenueImage::create([
                    'venue_id' => $venue->id,
                    'path' => $image['path'],
                    'alt_text' => $image['alt_text'] ?? null,
                    'sort_order' => $index,
                ]);
            }

            return $venue;
        });
    }
}
