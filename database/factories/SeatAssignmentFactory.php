<?php

namespace Database\Factories;

use App\Domain\Seating\Models\SeatAssignment;
use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Ticketing\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeatAssignment>
 */
class SeatAssignmentFactory extends Factory
{
    protected $model = SeatAssignment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'user_id' => User::factory(),
            'seat_plan_id' => SeatPlan::factory(),
            'seat_id' => (string) fake()->numberBetween(1, 9999),
        ];
    }
}
