<?php

namespace Database\Factories;

use App\Domain\Seating\Models\SeatAssignment;
use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Seating\Models\SeatPlanSeat;
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
        $plan = SeatPlan::factory()->create();
        $seat = $plan->seats()->first()
            ?? SeatPlanSeat::factory()->create(['seat_plan_id' => $plan->id]);

        return [
            'ticket_id' => Ticket::factory(),
            'user_id' => User::factory(),
            'seat_plan_id' => $plan->id,
            'seat_plan_seat_id' => $seat->id,
        ];
    }
}
