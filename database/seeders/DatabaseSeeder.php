<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->seedRoles();
        $this->seedUsers();
    }

    private function seedRoles(): void
    {
        $roles = [
            ['name' => RoleName::User->value, 'label' => 'User'],
            ['name' => RoleName::Admin->value, 'label' => 'Admin'],
            ['name' => RoleName::Superadmin->value, 'label' => 'Superadmin'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }

    private function seedUsers(): void
    {
        User::factory()->withRole(RoleName::User)->create([
            'name' => 'Test User',
            'email' => 'user@example.com',
        ]);

        User::factory()->withRole(RoleName::Admin)->create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
        ]);

        User::factory()->withRole(RoleName::Superadmin)->create([
            'name' => 'Test Superadmin',
            'email' => 'superadmin@example.com',
        ]);
    }
}
