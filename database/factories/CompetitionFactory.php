<?php

namespace Database\Factories;

use App\Domain\Competition\Enums\CompetitionStatus;
use App\Domain\Competition\Enums\CompetitionType;
use App\Domain\Competition\Enums\StageType;
use App\Domain\Competition\Models\Competition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Competition>
 */
class CompetitionFactory extends Factory
{
    protected $model = Competition::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'slug' => fake()->unique()->slug(3),
            'description' => fake()->paragraph(),
            'type' => CompetitionType::Tournament,
            'stage_type' => StageType::SingleElimination,
            'status' => CompetitionStatus::Draft,
            'team_size' => 5,
            'max_teams' => 16,
        ];
    }

    public function registrationOpen(): static
    {
        return $this->state([
            'status' => CompetitionStatus::RegistrationOpen,
            'registration_opens_at' => now()->subDay(),
            'registration_closes_at' => now()->addWeek(),
        ]);
    }

    public function registrationClosed(): static
    {
        return $this->state([
            'status' => CompetitionStatus::RegistrationClosed,
            'registration_opens_at' => now()->subWeek(),
            'registration_closes_at' => now()->subDay(),
        ]);
    }

    public function running(): static
    {
        return $this->state([
            'status' => CompetitionStatus::Running,
            'starts_at' => now()->subHour(),
        ]);
    }

    public function finished(): static
    {
        return $this->state([
            'status' => CompetitionStatus::Finished,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->subHour(),
        ]);
    }

    public function syncedToLanBrackets(): static
    {
        return $this->state([
            'lanbrackets_id' => fake()->numberBetween(1, 1000),
            'lanbrackets_share_token' => fake()->regexify('[a-zA-Z0-9]{32}'),
        ]);
    }

    public function withParticipantResults(): static
    {
        return $this->state([
            'settings' => ['result_submission_mode' => 'participants_with_proof'],
        ]);
    }
}
