<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Role;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with essential structural data.
     */
    public function run(): void
    {
        $this->seedRoles();
    }

    private function seedRoles(): void
    {
        $roles = [
            ['name' => RoleName::User->value, 'label' => 'User'],
            ['name' => RoleName::Admin->value, 'label' => 'Admin'],
            ['name' => RoleName::Superadmin->value, 'label' => 'Superadmin'],
            ['name' => RoleName::SponsorManager->value, 'label' => 'Sponsor Manager'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
