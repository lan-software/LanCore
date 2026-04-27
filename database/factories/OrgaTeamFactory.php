<?php

namespace Database\Factories;

use App\Domain\OrgaTeam\Models\OrgaTeam;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OrgaTeam>
 */
class OrgaTeamFactory extends Factory
{
    protected $model = OrgaTeam::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company().' Crew';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(5),
            'description' => fake()->sentence(),
            'organizer_user_id' => User::factory(),
        ];
    }
}
