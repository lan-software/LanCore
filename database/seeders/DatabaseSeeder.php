<?php

namespace Database\Seeders;

use App\Domain\Event\Models\Event;
use App\Domain\Venue\Models\Address;
use App\Domain\Venue\Models\Venue;
use App\Domain\Venue\Models\VenueImage;
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
        $this->seedVenues();
        $this->seedEvents();
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

    private function seedVenues(): void
    {
        $venue = Venue::factory()->create([
            'name' => 'Community Hall',
            'description' => 'A large community hall with excellent network infrastructure.',
            'address_id' => Address::factory()->create([
                'street' => 'Musterstraße 42',
                'city' => 'Berlin',
                'zip_code' => '10115',
                'state' => 'Berlin',
                'country' => 'Germany',
            ])->id,
        ]);

        VenueImage::factory()->count(2)->create(['venue_id' => $venue->id]);

        Venue::factory()->create([
            'name' => 'Tech Arena',
            'description' => 'Modern esports arena with 200 seats and streaming setup.',
            'address_id' => Address::factory()->create([
                'street' => 'Techpark 7',
                'city' => 'Munich',
                'zip_code' => '80333',
                'state' => 'Bavaria',
                'country' => 'Germany',
            ])->id,
        ]);
    }

    private function seedEvents(): void
    {
        $venue = Venue::query()->first();

        Event::factory()->create([
            'name' => 'Spring LAN 2026',
            'description' => 'Our annual spring LAN party with tournaments and fun.',
            'start_date' => '2026-04-10 18:00:00',
            'end_date' => '2026-04-12 16:00:00',
            'venue_id' => $venue?->id,
        ]);

        Event::factory()->published()->create([
            'name' => 'Summer LAN 2026',
            'description' => 'The biggest LAN event of the year.',
            'start_date' => '2026-07-15 16:00:00',
            'end_date' => '2026-07-18 14:00:00',
            'venue_id' => $venue?->id,
        ]);
    }
}
