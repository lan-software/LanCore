<?php

namespace App\Domain\Publishing\Http\Resources;

use App\Domain\Venue\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Maps a {@see Venue} (with its address and
 * published events eager-loaded) to a LAN Party Publishing Standard v2
 * venue object.
 *
 * @mixin Venue
 *
 * @see docs/mil-std-498/SRS.md PUB-F-002, PUB-F-004
 */
class LppsVenueResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array_filter([
            'apiVersion' => 2,
            'apiType' => 'Venue',
            'publisherUniqueId' => $this->id,
            'name' => $this->name,
            'gpsLatitude' => $this->address?->latitude,
            'gpsLongitude' => $this->address?->longitude,
            'countryCode' => $this->address?->country_code,
            'events' => LppsEventResource::collection($this->events)->resolve(),
        ], fn (mixed $value): bool => $value !== null);
    }
}
