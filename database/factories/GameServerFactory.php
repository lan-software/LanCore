<?php

namespace Database\Factories;

use App\Domain\Games\Models\Game;
use App\Domain\Orchestration\Enums\GameServerAllocationType;
use App\Domain\Orchestration\Enums\GameServerStatus;
use App\Domain\Orchestration\Models\GameServer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameServer>
 */
class GameServerFactory extends Factory
{
    protected $model = GameServer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true).' Server',
            'host' => fake()->ipv4(),
            'port' => fake()->numberBetween(27000, 28000),
            'game_id' => Game::factory(),
            'status' => GameServerStatus::Available,
            'allocation_type' => fake()->randomElement(GameServerAllocationType::cases()),
            'credentials' => ['rcon_password' => fake()->password(12)],
        ];
    }

    public function available(): static
    {
        return $this->state(['status' => GameServerStatus::Available]);
    }

    public function inUse(): static
    {
        return $this->state(['status' => GameServerStatus::InUse]);
    }

    public function offline(): static
    {
        return $this->state(['status' => GameServerStatus::Offline]);
    }

    public function maintenance(): static
    {
        return $this->state(['status' => GameServerStatus::Maintenance]);
    }

    public function competition(): static
    {
        return $this->state(['allocation_type' => GameServerAllocationType::Competition]);
    }

    public function casual(): static
    {
        return $this->state(['allocation_type' => GameServerAllocationType::Casual]);
    }

    public function flexible(): static
    {
        return $this->state(['allocation_type' => GameServerAllocationType::Flexible]);
    }
}
