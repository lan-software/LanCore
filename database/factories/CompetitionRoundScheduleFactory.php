<?php

namespace Database\Factories;

use App\Domain\CompetitionSchedule\Models\CompetitionRoundSchedule;
use App\Domain\CompetitionSchedule\Models\CompetitionStageSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompetitionRoundSchedule>
 */
class CompetitionRoundScheduleFactory extends Factory
{
    protected $model = CompetitionRoundSchedule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stage_schedule_id' => CompetitionStageSchedule::factory(),
            'lanbrackets_round_number' => fake()->unique()->numberBetween(1, 100000),
            'sequence' => 1,
            'label' => null,
            'starts_at' => null,
            'estimated_duration_minutes' => 30,
            'reserve_buffer_minutes' => 10,
            'duration_overridden' => false,
            'computed_inputs_hash' => null,
            'notes' => null,
        ];
    }

    public function scheduled(?\DateTimeInterface $startsAt = null): static
    {
        return $this->state([
            'starts_at' => $startsAt ?? now()->addHour(),
        ]);
    }

    public function overridden(): static
    {
        return $this->state([
            'duration_overridden' => true,
        ]);
    }
}
