<?php

namespace Database\Factories;

use App\Domain\Program\Enums\ProgramVisibility;
use App\Domain\Program\Models\Program;
use App\Domain\Program\Models\TimeSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeSlot>
 */
class TimeSlotFactory extends Factory
{
    protected $model = TimeSlot::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'starts_at' => fake()->dateTimeBetween('+1 week', '+6 months'),
            'visibility' => ProgramVisibility::Public,
            'program_id' => Program::factory(),
            'sort_order' => 0,
        ];
    }

    public function internal(): static
    {
        return $this->state(fn (array $attributes): array => [
            'visibility' => ProgramVisibility::Internal,
        ]);
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes): array => [
            'visibility' => ProgramVisibility::Private,
        ]);
    }
}
