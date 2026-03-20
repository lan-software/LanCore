<?php

namespace Database\Factories;

use App\Domain\Venue\Models\Venue;
use App\Domain\Venue\Models\VenueImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VenueImage>
 */
class VenueImageFactory extends Factory
{
    protected $model = VenueImage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'venue_id' => Venue::factory(),
            'path' => 'images/venues/'.fake()->uuid().'.jpg',
            'alt_text' => fake()->sentence(3),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
