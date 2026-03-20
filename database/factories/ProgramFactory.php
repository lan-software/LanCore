<?php

namespace Database\Factories;

use App\Domain\Event\Models\Event;
use App\Domain\Program\Enums\ProgramVisibility;
use App\Domain\Program\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    protected $model = Program::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'visibility' => ProgramVisibility::Public,
            'event_id' => Event::factory(),
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
