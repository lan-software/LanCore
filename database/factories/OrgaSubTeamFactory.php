<?php

namespace Database\Factories;

use App\Domain\OrgaTeam\Models\OrgaSubTeam;
use App\Domain\OrgaTeam\Models\OrgaTeam;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrgaSubTeam>
 */
class OrgaSubTeamFactory extends Factory
{
    protected $model = OrgaSubTeam::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'orga_team_id' => OrgaTeam::factory(),
            'name' => fake()->randomElement(['Tech', 'Marketing', 'Tournaments', 'General']).' '.fake()->unique()->randomNumber(3),
            'description' => fake()->sentence(),
            'emoji' => null,
            'color' => null,
            'sort_order' => 0,
            'leader_user_id' => null,
        ];
    }
}
