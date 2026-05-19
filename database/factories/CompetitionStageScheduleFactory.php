<?php

namespace Database\Factories;

use App\Domain\Competition\Models\Competition;
use App\Domain\CompetitionSchedule\Models\CompetitionStageSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompetitionStageSchedule>
 */
class CompetitionStageScheduleFactory extends Factory
{
    protected $model = CompetitionStageSchedule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'competition_id' => Competition::factory(),
            'lanbrackets_stage_id' => (string) fake()->unique()->numberBetween(1, 100000),
            'stage_name' => fake()->words(2, true),
            'stage_type' => 'single_elimination',
            'sequence' => 1,
            'starts_at' => null,
            'estimated_duration_minutes' => 90,
            'reserve_buffer_minutes' => 15,
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
