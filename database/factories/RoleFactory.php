<?php

namespace Database\Factories;

use App\Enums\RoleName;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $role = $this->faker->randomElement(RoleName::cases());

        return [
            'name' => $role->value,
            'label' => ucfirst($role->value),
        ];
    }
}
