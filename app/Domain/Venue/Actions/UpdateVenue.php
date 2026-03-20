<?php

namespace App\Domain\Venue\Actions;

use App\Domain\Venue\Models\Venue;
use App\Domain\Venue\Models\VenueImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdateVenue
{
    /**
     * @param  array{name: string, description?: string|null, street: string, city: string, zip_code: string, state?: string|null, country: string}  $attributes
     * @param  array<int, array{id: int, alt_text?: string|null}>  $existingImages
     * @param  array<int, array{path: string, alt_text?: string|null}>  $newImages
     */
    public function execute(Venue $venue, array $attributes, array $existingImages = [], array $newImages = []): void
    {
        DB::transaction(function () use ($venue, $attributes, $existingImages, $newImages): void {
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

            $keepIds = collect($existingImages)->pluck('id')->all();

            $venue->images()->whereNotIn('id', $keepIds)->each(function (VenueImage $image): void {
                Storage::delete($image->path);
                $image->delete();
            });

            $sortOrder = 0;
            foreach ($existingImages as $existing) {
                VenueImage::where('id', $existing['id'])->update([
                    'alt_text' => $existing['alt_text'] ?? null,
                    'sort_order' => $sortOrder++,
                ]);
            }

            foreach ($newImages as $image) {
                VenueImage::create([
                    'venue_id' => $venue->id,
                    'path' => $image['path'],
                    'alt_text' => $image['alt_text'] ?? null,
                    'sort_order' => $sortOrder++,
                ]);
            }
        });
    }
}
