<?php

namespace Database\Seeders;

use App\Domain\Event\Models\Event;
use App\Domain\Games\Models\Game;
use App\Domain\Games\Models\GameMode;
use App\Domain\Sponsoring\Models\Sponsor;
use App\Domain\Sponsoring\Models\SponsorLevel;
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
        $this->seedGames();
        $this->seedEvents();
        $this->seedSponsors();
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

    private function seedGames(): void
    {
        $cs2 = Game::factory()->create([
            'name' => 'Counter-Strike 2',
            'slug' => 'counter-strike-2',
            'publisher' => 'Valve',
            'description' => 'Tactical first-person shooter.',
        ]);

        GameMode::factory()->create([
            'game_id' => $cs2->id,
            'name' => '5v5 Competitive',
            'slug' => '5v5-competitive',
            'team_size' => 5,
            'parameters' => ['map_pool' => ['dust2', 'mirage', 'inferno', 'nuke', 'anubis', 'ancient', 'vertigo']],
        ]);

        GameMode::factory()->create([
            'game_id' => $cs2->id,
            'name' => '2v2 Wingman',
            'slug' => '2v2-wingman',
            'team_size' => 2,
            'parameters' => ['map_pool' => ['inferno', 'nuke', 'overpass']],
        ]);

        $lol = Game::factory()->create([
            'name' => 'League of Legends',
            'slug' => 'league-of-legends',
            'publisher' => 'Riot Games',
            'description' => 'Multiplayer online battle arena.',
        ]);

        GameMode::factory()->create([
            'game_id' => $lol->id,
            'name' => '5v5 Summoner\'s Rift',
            'slug' => '5v5-summoners-rift',
            'team_size' => 5,
        ]);

        $tableTennis = Game::factory()->create([
            'name' => 'Table Tennis',
            'slug' => 'table-tennis',
            'publisher' => null,
            'description' => 'Classic table tennis.',
        ]);

        GameMode::factory()->create([
            'game_id' => $tableTennis->id,
            'name' => '1v1 Singles',
            'slug' => '1v1-singles',
            'team_size' => 1,
        ]);

        GameMode::factory()->create([
            'game_id' => $tableTennis->id,
            'name' => '2v2 Doubles',
            'slug' => '2v2-doubles',
            'team_size' => 2,
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

    private function seedSponsors(): void
    {
        $gold = SponsorLevel::create(['name' => 'Gold', 'color' => '#FFD700', 'sort_order' => 0]);
        $silver = SponsorLevel::create(['name' => 'Silver', 'color' => '#C0C0C0', 'sort_order' => 1]);
        $bronze = SponsorLevel::create(['name' => 'Bronze', 'color' => '#CD7F32', 'sort_order' => 2]);

        $publishedEvent = Event::where('name', 'Summer LAN 2026')->first();

        $sponsor1 = Sponsor::factory()->create([
            'name' => 'TechCorp Gaming',
            'description' => 'Leading provider of gaming peripherals.',
            'link' => 'https://example.com/techcorp',
            'sponsor_level_id' => $gold->id,
        ]);

        $sponsor2 = Sponsor::factory()->create([
            'name' => 'NetSpeed ISP',
            'description' => 'High-speed internet for gamers.',
            'link' => 'https://example.com/netspeed',
            'sponsor_level_id' => $silver->id,
        ]);

        $sponsor3 = Sponsor::factory()->create([
            'name' => 'PixelDrink Energy',
            'description' => 'Energy drinks for late-night gaming sessions.',
            'sponsor_level_id' => $bronze->id,
        ]);

        if ($publishedEvent) {
            $publishedEvent->sponsors()->attach([$sponsor1->id, $sponsor2->id, $sponsor3->id]);
        }

        // Create a sponsor manager user
        $sponsorManager = User::factory()->withRole(RoleName::SponsorManager)->create([
            'name' => 'Sponsor Manager',
            'email' => 'sponsor@example.com',
        ]);

        $sponsor1->managers()->attach($sponsorManager->id);
    }
}
