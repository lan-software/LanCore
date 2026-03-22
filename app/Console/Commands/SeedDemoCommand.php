<?php

namespace App\Console\Commands;

use App\Domain\Event\Models\Event;
use App\Domain\Games\Models\Game;
use App\Domain\Games\Models\GameMode;
use App\Domain\News\Enums\ArticleVisibility;
use App\Domain\News\Models\NewsArticle;
use App\Domain\Program\Models\Program;
use App\Domain\Program\Models\TimeSlot;
use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Sponsoring\Models\Sponsor;
use App\Domain\Sponsoring\Models\SponsorLevel;
use App\Domain\Ticketing\Models\Addon;
use App\Domain\Ticketing\Models\TicketCategory;
use App\Domain\Ticketing\Models\TicketGroup;
use App\Domain\Ticketing\Models\TicketType;
use App\Domain\Venue\Models\Address;
use App\Domain\Venue\Models\Venue;
use App\Domain\Venue\Models\VenueImage;
use App\Domain\Webhook\Enums\WebhookEvent;
use App\Domain\Webhook\Models\Webhook;
use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('db:seed-demo')]
#[Description('Seed the database with demo data including events, venues, ticket types, test users, and more')]
class SeedDemoCommand extends Command
{
    public function handle(): int
    {
        $this->call('db:seed');

        $this->info('Seeding demo data...');
        $this->newLine();

        /** @var array<string, string> $results */
        $results = [];

        $this->attempt('Users', $results, fn () => $this->seedUsers());
        $this->attempt('Venues', $results, fn () => $this->seedVenues());
        $this->attempt('Games', $results, fn () => $this->seedGames());

        /** @var array{published: Event, draft: Event, past: Event}|null $events */
        $events = $this->attempt(
            'Events',
            $results,
            fn () => $this->seedEvents(),
            fn () => $this->loadExistingEvents(),
        );

        if ($events !== null) {
            $this->attempt('Ticketing', $results, fn () => $this->seedTicketing($events));
            $this->attempt('Programs', $results, fn () => $this->seedPrograms($events));
            $this->attempt('Sponsors', $results, fn () => $this->seedSponsors($events));
        } else {
            $results['Ticketing'] = 'Skipped — events not seeded';
            $results['Programs'] = 'Skipped — events not seeded';
            $results['Sponsors'] = 'Skipped — events not seeded';
        }

        $this->attempt('News', $results, fn () => $this->seedNews());
        $this->attempt('Webhooks', $results, fn () => $this->seedWebhooks());

        $this->newLine();
        $this->info('Seeding summary:');
        $this->newLine();

        foreach ($results as $label => $status) {
            if ($status === 'ok') {
                $this->components->twoColumnDetail($label, '<fg=green>✓ Seeded</>');
            } else {
                $this->components->twoColumnDetail($label, "<fg=yellow>↷ {$status}</>");
            }
        }

        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * @param  array<string, string>  $results
     */
    private function attempt(string $label, array &$results, callable $callback, ?callable $fallback = null): mixed
    {
        try {
            $value = $callback();

            if ($value === false) {
                $results[$label] = 'Already seeded, no changes made';

                return $fallback !== null ? $fallback() : null;
            }

            $results[$label] = 'ok';

            return $value;
        } catch (Throwable $e) {
            $results[$label] = 'Failed: '.$e->getMessage();

            return null;
        }
    }

    private function seedUsers(): bool
    {
        if (User::query()->where('email', 'user@example.com')->exists()) {
            return false;
        }

        $this->components->task('Seeding users', function (): void {
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

            User::factory()->withRole(RoleName::SponsorManager)->create([
                'name' => 'Sponsor Manager',
                'email' => 'sponsor@example.com',
            ]);

            User::factory()->withRole(RoleName::User)->count(20)->create();
        });

        return true;
    }

    private function seedVenues(): bool
    {
        if (Venue::query()->where('name', 'Community Hall')->exists()) {
            return false;
        }

        $this->components->task('Seeding venues', function (): void {
            $communityHall = Venue::factory()->create([
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

            VenueImage::factory()->count(3)->create(['venue_id' => $communityHall->id]);

            $techArena = Venue::factory()->create([
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

            VenueImage::factory()->count(2)->create(['venue_id' => $techArena->id]);

            Venue::factory()->create([
                'name' => 'Turnhalle Mitte',
                'description' => 'A renovated gym perfect for medium-sized LAN events.',
                'address_id' => Address::factory()->create([
                    'street' => 'Sportplatz 1',
                    'city' => 'Hamburg',
                    'zip_code' => '20095',
                    'state' => 'Hamburg',
                    'country' => 'Germany',
                ])->id,
            ]);
        });

        return true;
    }

    private function seedGames(): bool
    {
        if (Game::query()->where('slug', 'counter-strike-2')->exists()) {
            return false;
        }

        $this->components->task('Seeding games', function (): void {
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

            $valorant = Game::factory()->create([
                'name' => 'Valorant',
                'slug' => 'valorant',
                'publisher' => 'Riot Games',
                'description' => 'Tactical hero-based shooter.',
            ]);

            GameMode::factory()->create([
                'game_id' => $valorant->id,
                'name' => '5v5 Competitive',
                'slug' => '5v5-competitive-valorant',
                'team_size' => 5,
            ]);

            $rocketLeague = Game::factory()->create([
                'name' => 'Rocket League',
                'slug' => 'rocket-league',
                'publisher' => 'Psyonix',
                'description' => 'Soccer with rocket-powered cars.',
            ]);

            GameMode::factory()->create([
                'game_id' => $rocketLeague->id,
                'name' => '3v3 Standard',
                'slug' => '3v3-standard',
                'team_size' => 3,
            ]);

            GameMode::factory()->create([
                'game_id' => $rocketLeague->id,
                'name' => '2v2 Doubles',
                'slug' => '2v2-doubles-rl',
                'team_size' => 2,
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
                'slug' => '2v2-doubles-tt',
                'team_size' => 2,
            ]);
        });

        return true;
    }

    /**
     * @return array{published: Event, draft: Event, past: Event}|false
     */
    private function seedEvents(): array|false
    {
        if (Event::query()->where('name', 'Summer LAN 2026')->exists()) {
            return false;
        }

        $events = [];

        $this->components->task('Seeding events', function () use (&$events): void {
            $venues = Venue::all();

            $events['published'] = Event::factory()->published()->create([
                'name' => 'Summer LAN 2026',
                'description' => 'The biggest LAN event of the year with tournaments, workshops, and fun.',
                'start_date' => '2026-07-15 16:00:00',
                'end_date' => '2026-07-18 14:00:00',
                'venue_id' => $venues->first()?->id,
            ]);

            $events['draft'] = Event::factory()->create([
                'name' => 'Spring LAN 2026',
                'description' => 'Our annual spring LAN party with tournaments and fun.',
                'start_date' => '2026-04-10 18:00:00',
                'end_date' => '2026-04-12 16:00:00',
                'venue_id' => $venues->skip(1)->first()?->id,
            ]);

            $events['past'] = Event::factory()->published()->create([
                'name' => 'Winter LAN 2025',
                'description' => 'Last year\'s winter edition — a huge success.',
                'start_date' => '2025-12-27 14:00:00',
                'end_date' => '2025-12-29 18:00:00',
                'venue_id' => $venues->first()?->id,
            ]);
        });

        return $events;
    }

    /**
     * @return array{published: Event, draft: Event, past: Event}|null
     */
    private function loadExistingEvents(): ?array
    {
        $published = Event::query()->where('name', 'Summer LAN 2026')->first();
        $draft = Event::query()->where('name', 'Spring LAN 2026')->first();
        $past = Event::query()->where('name', 'Winter LAN 2025')->first();

        if ($published === null || $draft === null || $past === null) {
            return null;
        }

        return compact('published', 'draft', 'past');
    }

    /**
     * @param  array{published: Event, draft: Event, past: Event}  $events
     */
    private function seedTicketing(array $events): bool
    {
        if (TicketCategory::query()->where('event_id', $events['published']->id)->exists()) {
            return false;
        }

        $this->components->task('Seeding ticket types & addons', function () use ($events): void {
            foreach ([$events['published'], $events['draft']] as $event) {
                $category = TicketCategory::factory()->create([
                    'name' => 'Seats',
                    'description' => 'All seat-based ticket types.',
                    'event_id' => $event->id,
                ]);

                $group = TicketGroup::factory()->create([
                    'name' => 'General Admission',
                    'description' => 'Standard ticket group.',
                    'event_id' => $event->id,
                ]);

                TicketType::factory()->create([
                    'name' => 'Standard Seat',
                    'description' => 'A regular seat with power and network.',
                    'price' => 3500,
                    'quota' => 100,
                    'event_id' => $event->id,
                    'ticket_category_id' => $category->id,
                    'ticket_group_id' => $group->id,
                ]);

                TicketType::factory()->create([
                    'name' => 'Premium Seat',
                    'description' => 'Extra legroom and a wider desk.',
                    'price' => 5500,
                    'quota' => 30,
                    'event_id' => $event->id,
                    'ticket_category_id' => $category->id,
                    'ticket_group_id' => $group->id,
                ]);

                TicketType::factory()->create([
                    'name' => 'VIP Seat',
                    'description' => 'VIP area with catering and lounge access.',
                    'price' => 9500,
                    'quota' => 10,
                    'event_id' => $event->id,
                    'ticket_category_id' => $category->id,
                ]);

                TicketType::factory()->rowTicket(5)->create([
                    'name' => 'Clan Row (5 seats)',
                    'description' => 'A row of 5 seats for your clan.',
                    'price' => 15000,
                    'quota' => 10,
                    'event_id' => $event->id,
                    'ticket_category_id' => $category->id,
                ]);

                Addon::factory()->create([
                    'name' => '10 Gbit Ethernet Upgrade',
                    'description' => 'Upgrade your port to 10 Gbit Ethernet.',
                    'price' => 1500,
                    'quota' => 50,
                    'event_id' => $event->id,
                ]);

                Addon::factory()->create([
                    'name' => 'Premium Chair',
                    'description' => 'Ergonomic gaming chair upgrade.',
                    'price' => 2000,
                    'quota' => 20,
                    'event_id' => $event->id,
                ]);

                Addon::factory()->create([
                    'name' => 'Coffee Flatrate',
                    'description' => 'Unlimited coffee for the entire event.',
                    'price' => 800,
                    'event_id' => $event->id,
                ]);

                SeatPlan::factory()->create([
                    'name' => 'Main Hall',
                    'event_id' => $event->id,
                ]);
            }
        });

        return true;
    }

    /**
     * @param  array{published: Event, draft: Event, past: Event}  $events
     */
    private function seedPrograms(array $events): bool
    {
        if (Program::query()->where('event_id', $events['published']->id)->exists()) {
            return false;
        }

        $this->components->task('Seeding programs & time slots', function () use ($events): void {
            $publishedEvent = $events['published'];

            $mainProgram = Program::factory()->create([
                'name' => 'Main Stage',
                'description' => 'Tournaments and main attractions.',
                'event_id' => $publishedEvent->id,
                'sort_order' => 0,
            ]);

            $publishedEvent->update(['primary_program_id' => $mainProgram->id]);

            TimeSlot::factory()->create([
                'name' => 'Opening Ceremony',
                'description' => 'Welcome and schedule overview.',
                'starts_at' => '2026-07-15 16:00:00',
                'program_id' => $mainProgram->id,
                'sort_order' => 0,
            ]);

            TimeSlot::factory()->create([
                'name' => 'CS2 Tournament — Group Stage',
                'description' => 'Counter-Strike 2 group stage matches.',
                'starts_at' => '2026-07-15 18:00:00',
                'program_id' => $mainProgram->id,
                'sort_order' => 1,
            ]);

            TimeSlot::factory()->create([
                'name' => 'CS2 Tournament — Finals',
                'description' => 'Counter-Strike 2 grand final.',
                'starts_at' => '2026-07-17 14:00:00',
                'program_id' => $mainProgram->id,
                'sort_order' => 2,
            ]);

            TimeSlot::factory()->create([
                'name' => 'Closing Ceremony & Prize Giving',
                'description' => 'Award ceremony and farewell.',
                'starts_at' => '2026-07-18 12:00:00',
                'program_id' => $mainProgram->id,
                'sort_order' => 3,
            ]);

            $sideProgram = Program::factory()->create([
                'name' => 'Side Activities',
                'description' => 'Casual games, workshops, and social events.',
                'event_id' => $publishedEvent->id,
                'sort_order' => 1,
            ]);

            TimeSlot::factory()->create([
                'name' => 'Table Tennis Tournament',
                'description' => 'Casual table tennis competition.',
                'starts_at' => '2026-07-16 10:00:00',
                'program_id' => $sideProgram->id,
                'sort_order' => 0,
            ]);

            TimeSlot::factory()->create([
                'name' => 'Network Workshop',
                'description' => 'Learn how to crimp your own LAN cables.',
                'starts_at' => '2026-07-16 14:00:00',
                'program_id' => $sideProgram->id,
                'sort_order' => 1,
            ]);

            $crewProgram = Program::factory()->internal()->create([
                'name' => 'Crew Schedule',
                'description' => 'Internal crew shifts and logistics.',
                'event_id' => $publishedEvent->id,
                'sort_order' => 2,
            ]);

            TimeSlot::factory()->internal()->create([
                'name' => 'Network Setup',
                'description' => 'Set up switches, patch panels, and cables.',
                'starts_at' => '2026-07-15 08:00:00',
                'program_id' => $crewProgram->id,
                'sort_order' => 0,
            ]);

            TimeSlot::factory()->internal()->create([
                'name' => 'Teardown',
                'description' => 'Disassemble infrastructure.',
                'starts_at' => '2026-07-18 15:00:00',
                'program_id' => $crewProgram->id,
                'sort_order' => 1,
            ]);
        });

        return true;
    }

    /**
     * @param  array{published: Event, draft: Event, past: Event}  $events
     */
    private function seedSponsors(array $events): bool
    {
        if (SponsorLevel::query()->where('name', 'Gold')->exists()) {
            return false;
        }

        $this->components->task('Seeding sponsors', function () use ($events): void {
            $gold = SponsorLevel::create(['name' => 'Gold', 'color' => '#FFD700', 'sort_order' => 0]);
            $silver = SponsorLevel::create(['name' => 'Silver', 'color' => '#C0C0C0', 'sort_order' => 1]);
            $bronze = SponsorLevel::create(['name' => 'Bronze', 'color' => '#CD7F32', 'sort_order' => 2]);

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

            $sponsor4 = Sponsor::factory()->create([
                'name' => 'CloudHost Pro',
                'description' => 'Game server hosting for competitive events.',
                'link' => 'https://example.com/cloudhost',
                'sponsor_level_id' => $silver->id,
            ]);

            $publishedEvent = $events['published'];
            $publishedEvent->sponsors()->attach([
                $sponsor1->id,
                $sponsor2->id,
                $sponsor3->id,
                $sponsor4->id,
            ]);

            $sponsorManager = User::query()->where('email', 'sponsor@example.com')->first();
            if ($sponsorManager) {
                $sponsor1->managers()->attach($sponsorManager->id);
                $sponsor4->managers()->attach($sponsorManager->id);
            }
        });

        return true;
    }

    private function seedNews(): bool
    {
        if (NewsArticle::query()->where('title', 'Summer LAN 2026 Announced!')->exists()) {
            return false;
        }

        $this->components->task('Seeding news articles', function (): void {
            $admin = User::query()->where('email', 'admin@example.com')->first();

            NewsArticle::factory()->published()->create([
                'title' => 'Summer LAN 2026 Announced!',
                'summary' => 'We are excited to announce the biggest LAN party of the year.',
                'content' => '<p>Mark your calendars! Summer LAN 2026 will take place from July 15-18 at the Community Hall in Berlin.</p><p>Expect epic tournaments, amazing prizes, and a weekend of non-stop gaming.</p>',
                'tags' => ['announcement', 'event'],
                'author_id' => $admin?->id ?? User::factory()->withRole(RoleName::Admin)->create()->id,
                'published_at' => '2026-01-15 10:00:00',
            ]);

            NewsArticle::factory()->published()->create([
                'title' => 'Ticket Sales Now Open',
                'summary' => 'Grab your tickets before they sell out!',
                'content' => '<p>Standard, Premium, and VIP seats are now available. Clan rows can be booked for groups of 5.</p>',
                'tags' => ['announcement', 'event'],
                'author_id' => $admin?->id ?? User::factory()->withRole(RoleName::Admin)->create()->id,
                'published_at' => '2026-02-01 12:00:00',
            ]);

            NewsArticle::factory()->create([
                'title' => 'Tournament Rules Draft',
                'summary' => 'Review the draft tournament rules.',
                'visibility' => ArticleVisibility::Draft,
                'author_id' => $admin?->id ?? User::factory()->withRole(RoleName::Admin)->create()->id,
            ]);
        });

        return true;
    }

    private function seedWebhooks(): bool
    {
        if (Webhook::query()->where('url', 'http://mockserver:1080/webhooks/user-registered')->exists()) {
            return false;
        }

        $this->components->task('Seeding webhooks', function (): void {
            Webhook::factory()->create([
                'name' => 'MockServer — User Registered',
                'description' => 'Development webhook that forwards user registration events to MockServer for inspection.',
                'url' => 'http://mockserver:1080/webhooks/user-registered',
                'event' => WebhookEvent::UserRegistered,
                'secret' => null,
                'is_active' => true,
            ]);

            Webhook::factory()->create([
                'name' => 'MockServer — Announcement Published',
                'description' => 'Development webhook that forwards announcement published events to MockServer for inspection.',
                'url' => 'http://mockserver:1080/webhooks/announcement-published',
                'event' => WebhookEvent::AnnouncementPublished,
                'secret' => null,
                'is_active' => true,
            ]);

            Webhook::factory()->create([
                'name' => 'MockServer — News Article Published',
                'description' => 'Development webhook that forwards news article published events to MockServer for inspection.',
                'url' => 'http://mockserver:1080/webhooks/news-article-published',
                'event' => WebhookEvent::NewsArticlePublished,
                'secret' => null,
                'is_active' => true,
            ]);

            Webhook::factory()->create([
                'name' => 'MockServer — Event Published',
                'description' => 'Development webhook that forwards event published events to MockServer for inspection.',
                'url' => 'http://mockserver:1080/webhooks/event-published',
                'event' => WebhookEvent::EventPublished,
                'secret' => null,
                'is_active' => true,
            ]);
        });

        return true;
    }
}
