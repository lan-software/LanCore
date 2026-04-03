<?php

namespace Database\Factories;

use App\Domain\Event\Models\Event;
use App\Domain\Shop\Models\Order;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => TicketStatus::Active,
            'validation_id' => strtoupper(Str::random(16)),
            'checked_in_at' => null,
            'ticket_type_id' => TicketType::factory(),
            'event_id' => Event::factory(),
            'order_id' => Order::factory(),
            'owner_id' => User::factory(),
            'manager_id' => null,
        ];
    }

    public function checkedIn(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TicketStatus::CheckedIn,
            'checked_in_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TicketStatus::Cancelled,
        ]);
    }
}
