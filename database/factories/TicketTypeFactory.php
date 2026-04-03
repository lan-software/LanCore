<?php

namespace Database\Factories;

use App\Domain\Event\Models\Event;
use App\Domain\Ticketing\Models\TicketCategory;
use App\Domain\Ticketing\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketType>
 */
class TicketTypeFactory extends Factory
{
    protected $model = TicketType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $purchaseFrom = fake()->dateTimeBetween('now', '+1 week');

        return [
            'name' => fake()->randomElement(['Standard Seat', 'Premium Seat', 'Clan Row', 'VIP Seat', 'Economy Seat']),
            'description' => fake()->sentence(),
            'price' => fake()->numberBetween(1500, 10000),
            'quota' => fake()->numberBetween(10, 200),
            'seats_per_ticket' => 1,
            'is_row_ticket' => false,
            'is_seatable' => true,
            'is_hidden' => false,
            'purchase_from' => $purchaseFrom,
            'purchase_until' => fake()->dateTimeBetween($purchaseFrom, '+6 months'),
            'is_locked' => false,
            'event_id' => Event::factory(),
            'ticket_category_id' => null,
            'ticket_group_id' => null,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_hidden' => true,
        ]);
    }

    /**
     * @deprecated Use groupTicket() instead. Row ticket logic is deprecated.
     */
    public function rowTicket(int $seatsPerTicket = 5): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_row_ticket' => true,
            'seats_per_ticket' => $seatsPerTicket,
        ]);
    }

    public function locked(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_locked' => true,
        ]);
    }

    public function withCategory(): static
    {
        return $this->state(fn (array $attributes): array => [
            'ticket_category_id' => TicketCategory::factory(),
        ]);
    }
}
