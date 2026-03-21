<?php

namespace Database\Factories;

use App\Domain\Event\Models\Event;
use App\Domain\Ticketing\Models\TicketGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketGroup>
 */
class TicketGroupFactory extends Factory
{
    protected $model = TicketGroup::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true).' Group',
            'description' => fake()->sentence(),
            'event_id' => Event::factory(),
        ];
    }
}
