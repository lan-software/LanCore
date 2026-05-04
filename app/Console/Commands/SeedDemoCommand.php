<?php

namespace App\Console\Commands;

use App\Domain\Achievements\Enums\GrantableEvent;
use App\Domain\Achievements\Models\Achievement;
use App\Domain\Announcement\Enums\AnnouncementAudience;
use App\Domain\Announcement\Enums\AnnouncementPriority;
use App\Domain\Announcement\Models\Announcement;
use App\Domain\Competition\Enums\CompetitionStatus;
use App\Domain\Competition\Enums\CompetitionType;
use App\Domain\Competition\Enums\ResultSubmissionMode;
use App\Domain\Competition\Enums\StageType;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\CompetitionTeam;
use App\Domain\Competition\Models\CompetitionTeamMember;
use App\Domain\Event\Models\Event;
use App\Domain\Games\Models\Game;
use App\Domain\Games\Models\GameMode;
use App\Domain\Integration\Models\IntegrationApp;
use App\Domain\News\Enums\ArticleVisibility;
use App\Domain\News\Models\NewsArticle;
use App\Domain\OrgaTeam\Models\OrgaTeam;
use App\Domain\Program\Models\Program;
use App\Domain\Program\Models\TimeSlot;
use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Shop\Enums\PaymentMethod;
use App\Domain\Shop\Models\GlobalPurchaseCondition;
use App\Domain\Shop\Models\PaymentProviderCondition;
use App\Domain\Shop\Models\PurchaseRequirement;
use App\Domain\Sponsoring\Models\Sponsor;
use App\Domain\Sponsoring\Models\SponsorLevel;
use App\Domain\Theme\Models\Theme;
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
use App\Models\OrganizationSetting;
use App\Models\User;
use App\Support\StorageRole;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\File;
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
        $this->attempt('Themes', $results, fn () => $this->seedThemes());
        $this->attempt('Orga Teams', $results, fn () => $this->seedOrgaTeams());

        /** @var array{published: Event, draft: Event, past: Event}|null $events */
        $events = $this->attempt(
            'Events',
            $results,
            fn () => $this->seedEvents(),
            fn () => $this->loadExistingEvents(),
        );

        if ($events !== null) {
            $this->attempt('Ticketing', $results, fn () => $this->seedTicketing($events));
            $this->attempt('Seat Plans', $results, fn () => $this->seedSeatPlans($events));
            $this->attempt('Programs', $results, fn () => $this->seedPrograms($events));
            $this->attempt('Sponsors', $results, fn () => $this->seedSponsors($events));

            $hasBrackets = IntegrationApp::query()->where('slug', 'lanbrackets')->where('is_active', true)->exists();
            if ($hasBrackets) {
                $this->attempt('Competitions', $results, fn () => $this->seedCompetitions($events));
            } else {
                $results['Competitions'] = 'Skipped — LanBrackets integration not configured (run integration:setup-dev lanbrackets first)';
            }
        } else {
            $results['Ticketing'] = 'Skipped — events not seeded';
            $results['Seat Plans'] = 'Skipped — events not seeded';
            $results['Programs'] = 'Skipped — events not seeded';
            $results['Sponsors'] = 'Skipped — events not seeded';
            $results['Competitions'] = 'Skipped — events not seeded';
        }

        $this->attempt('News', $results, fn () => $this->seedNews());
        $this->attempt('Achievements', $results, fn () => $this->seedAchievements());
        $this->attempt('Shop Conditions', $results, fn () => $this->seedShopConditions());
        $this->attempt('Organization', $results, fn () => $this->seedOrganization());
        $this->attempt('Webhooks', $results, fn () => $this->seedWebhooks());
        $this->attempt('Announcements', $results, fn () => $this->seedDemoAnnouncement());

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

    /**
     * Demo venues used by `db:seed-demo`.
     *
     * The accompanying images live in `database/seeders/images/venues/demo-venu-{n}/`
     * and are AI-generated demo material (watermarked accordingly). The original
     * generation prompts are kept here for traceability of the demo dataset.
     *
     * @return list<array{
     *     name: string,
     *     description: string,
     *     street: string,
     *     zip_code: string,
     *     city: string,
     *     state: string,
     *     country: string,
     *     image_dir: string,
     *     image_prompts: list<string>
     * }>
     */
    private function demoVenues(): array
    {
        return [
            [
                'name' => 'Hafenwerk Stralsund',
                'description' => 'Das Hafenwerk Stralsund ist eine moderne Eventhalle im industrial Look direkt am Wasser. Die Venue eignet sich für mittelgroße bis große Veranstaltungen, Messen und Community-Formate. Große offene Flächen, hohe Decken, gute Stromversorgung und getrennte Nebenbereiche für Empfang, Catering oder Backoffice machen die Location vielseitig nutzbar.',
                'street' => 'Werftstraße 18',
                'zip_code' => '18439',
                'city' => 'Stralsund',
                'state' => 'Mecklenburg-Vorpommern',
                'country' => 'Deutschland',
                'image_dir' => 'demo-venu-1',
                'image_prompts' => [
                    'A realistic architectural photo of a modern industrial event venue at a harbor in northern Germany, large brick building with steel elements, clean forecourt, no people, no event setup, overcast daylight, professional real estate photography',
                    'A realistic interior photo of a large empty industrial event hall with high ceilings, exposed steel beams, polished concrete floor, clean walls, no chairs, no tables, no stage, no people, professional venue photography',
                    'A realistic photo of a modern venue entrance foyer with industrial interior design, reception area, wide walkways, clean lighting, empty and tidy, no people, no branding, professional commercial interior photography',
                ],
            ],
            [
                'name' => 'Campus Forum Greifswald',
                'description' => 'Das Campus Forum Greifswald ist ein heller, funktionaler Veranstaltungsort in Hochschulnähe. Die Venue eignet sich für Tagungen, Workshops, kleinere Messen und studentische Veranstaltungen. Mehrere kombinierbare Räume, offene Architektur und eine sachlich-moderne Gestaltung machen sie zu einer flexiblen Demo-Location.',
                'street' => 'Ernst-Lohmeyer-Platz 6',
                'zip_code' => '17489',
                'city' => 'Greifswald',
                'state' => 'Mecklenburg-Vorpommern',
                'country' => 'Deutschland',
                'image_dir' => 'demo-venu-2',
                'image_prompts' => [
                    'A realistic architectural photo of a contemporary university event building in Germany, modern facade, wide glass entrance, clean surroundings, no people, no banners, no event setup, daylight, professional property photography',
                    'A realistic interior photo of a bright modern event hall with clean flooring, white walls, ceiling lights, flexible open space, no furniture, no people, no stage equipment, professional commercial real estate photography',
                    'A realistic photo of a modern lounge or seminar area inside a campus event venue, minimal interior, natural light, clean seating corners, tidy and empty, no people, no signage, professional venue brochure photography',
                ],
            ],
            [
                'name' => 'Nordlicht Messehalle Rostock',
                'description' => 'Die Nordlicht Messehalle Rostock ist eine große, flexible Veranstaltungsfläche für Messen, Kongresse und größere Events. Sie bietet viel Platz für variable Flächenkonzepte, mehrere Hallenbereiche und logistisch gut nutzbare Zugänge. Für LanCore-Demos ist sie ideal, um größere Venue-Strukturen mit mehreren Bereichen abzubilden.',
                'street' => 'Zur HanseMesse 1',
                'zip_code' => '18106',
                'city' => 'Rostock',
                'state' => 'Mecklenburg-Vorpommern',
                'country' => 'Deutschland',
                'image_dir' => 'demo-venu-3',
                'image_prompts' => [
                    'A realistic architectural photo of a large modern exhibition hall in northern Germany, broad entrance plaza, clean facade, no people, no event branding, no vehicles in focus, cloudy daylight, professional real estate photography',
                    'A realistic interior photo of a huge empty exhibition hall with wide open floor space, high ceiling structure, industrial lighting, clean walls, no booths, no stage, no people, professional venue documentation photography',
                    'A realistic photo of the main entrance or interior access corridor of a modern exhibition center, spacious, clean, neutral design, no people, no event signage, no temporary installations, professional commercial photography',
                ],
            ],
        ];
    }

    private function seedVenues(): bool
    {
        if (Venue::query()->where('name', 'Hafenwerk Stralsund')->exists()) {
            return false;
        }

        $this->components->task('Seeding venues', function (): void {
            foreach ($this->demoVenues() as $data) {
                $venue = Venue::factory()->create([
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'address_id' => Address::factory()->create([
                        'street' => $data['street'],
                        'city' => $data['city'],
                        'zip_code' => $data['zip_code'],
                        'state' => $data['state'],
                        'country' => $data['country'],
                    ])->id,
                ]);

                $sourceDir = database_path('seeders/images/venues/'.$data['image_dir']);
                $sources = glob($sourceDir.'/*.png') ?: [];
                sort($sources);

                foreach ($sources as $index => $source) {
                    $storedPath = $this->copyDemoImageToStorage($source, 'venues/images');

                    VenueImage::create([
                        'venue_id' => $venue->id,
                        'path' => $storedPath,
                        'alt_text' => $data['name'].' – AI-generated demo image',
                        'sort_order' => $index,
                    ]);
                }
            }
        });

        return true;
    }

    private function copyDemoImageToStorage(string $sourcePath, string $directory): string
    {
        // putFileAs (not put) so the file's MIME type and the disk's visibility
        // config are honored — required for S3-backed public disks to serve PNGs.
        return (string) StorageRole::public()->putFileAs(
            $directory,
            new File($sourcePath),
            basename($sourcePath),
        );
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

    private function seedThemes(): bool
    {
        if (Theme::query()->where('name', 'Aurora')->exists()) {
            return false;
        }

        $this->components->task('Seeding themes', function (): void {
            Theme::create([
                'name' => 'Aurora',
                'description' => 'Cool teal-and-violet palette for summer events.',
                'light_config' => [
                    '--background' => '#f5fbff',
                    '--foreground' => '#0e1726',
                    '--card' => '#ffffff',
                    '--card-foreground' => '#0e1726',
                    '--primary' => '#0d9488',
                    '--primary-foreground' => '#ffffff',
                    '--secondary' => '#e0f2fe',
                    '--secondary-foreground' => '#0c4a6e',
                    '--accent' => '#a78bfa',
                    '--accent-foreground' => '#1e1b4b',
                    '--muted' => '#eef2f6',
                    '--muted-foreground' => '#475569',
                    '--border' => '#dbeafe',
                    '--ring' => '#0d9488',
                    '--sidebar-background' => '#0f172a',
                    '--sidebar-foreground' => '#cbd5f5',
                    '--sidebar-primary' => '#22d3ee',
                    '--sidebar-primary-foreground' => '#0f172a',
                    '--sidebar-accent' => '#1e293b',
                    '--sidebar-accent-foreground' => '#e2e8f0',
                    '--sidebar-border' => '#1e293b',
                ],
                'dark_config' => [
                    '--background' => '#020617',
                    '--foreground' => '#f1f5f9',
                    '--card' => '#0b1220',
                    '--card-foreground' => '#f1f5f9',
                    '--primary' => '#2dd4bf',
                    '--primary-foreground' => '#042f2e',
                    '--secondary' => '#1e293b',
                    '--secondary-foreground' => '#cbd5f5',
                    '--accent' => '#a78bfa',
                    '--accent-foreground' => '#1e1b4b',
                    '--muted' => '#0f172a',
                    '--muted-foreground' => '#94a3b8',
                    '--border' => '#1e293b',
                    '--ring' => '#2dd4bf',
                    '--sidebar-background' => '#020617',
                    '--sidebar-foreground' => '#cbd5f5',
                    '--sidebar-primary' => '#22d3ee',
                    '--sidebar-primary-foreground' => '#020617',
                    '--sidebar-accent' => '#0f172a',
                    '--sidebar-accent-foreground' => '#e2e8f0',
                    '--sidebar-border' => '#0f172a',
                ],
            ]);

            Theme::create([
                'name' => 'Sunset Arcade',
                'description' => 'Warm orange-and-magenta palette for retro-flavored events.',
                'light_config' => [
                    '--background' => '#fff7ed',
                    '--foreground' => '#27121a',
                    '--card' => '#ffffff',
                    '--card-foreground' => '#27121a',
                    '--primary' => '#ea580c',
                    '--primary-foreground' => '#ffffff',
                    '--secondary' => '#fde68a',
                    '--secondary-foreground' => '#7c2d12',
                    '--accent' => '#db2777',
                    '--accent-foreground' => '#ffffff',
                    '--muted' => '#ffe4d3',
                    '--muted-foreground' => '#8b3a1d',
                    '--border' => '#fbd9b9',
                    '--ring' => '#ea580c',
                    '--sidebar-background' => '#27121a',
                    '--sidebar-foreground' => '#fde7d6',
                    '--sidebar-primary' => '#fb923c',
                    '--sidebar-primary-foreground' => '#27121a',
                    '--sidebar-accent' => '#3b1a26',
                    '--sidebar-accent-foreground' => '#fde7d6',
                    '--sidebar-border' => '#3b1a26',
                ],
                'dark_config' => [
                    '--background' => '#1c0d12',
                    '--foreground' => '#fde7d6',
                    '--card' => '#27121a',
                    '--card-foreground' => '#fde7d6',
                    '--primary' => '#fb923c',
                    '--primary-foreground' => '#27121a',
                    '--secondary' => '#3b1a26',
                    '--secondary-foreground' => '#fde7d6',
                    '--accent' => '#f472b6',
                    '--accent-foreground' => '#27121a',
                    '--muted' => '#2a1119',
                    '--muted-foreground' => '#d8a89a',
                    '--border' => '#3b1a26',
                    '--ring' => '#fb923c',
                    '--sidebar-background' => '#160a0f',
                    '--sidebar-foreground' => '#fde7d6',
                    '--sidebar-primary' => '#fb923c',
                    '--sidebar-primary-foreground' => '#160a0f',
                    '--sidebar-accent' => '#27121a',
                    '--sidebar-accent-foreground' => '#fde7d6',
                    '--sidebar-border' => '#27121a',
                ],
            ]);
        });

        return true;
    }

    private function seedOrgaTeams(): bool
    {
        if (OrgaTeam::query()->where('slug', 'lan-party-crew')->exists()) {
            return false;
        }

        $organizer = User::query()->where('email', 'admin@example.com')->first();

        if ($organizer === null) {
            return false;
        }

        $this->components->task('Seeding orga teams', function () use ($organizer): void {
            $team = OrgaTeam::create([
                'name' => 'LAN Party Crew',
                'slug' => 'lan-party-crew',
                'description' => 'The core organizing crew running every LAN event in this demo dataset.',
                'organizer_user_id' => $organizer->id,
            ]);

            $deputies = User::query()
                ->whereIn('email', ['superadmin@example.com', 'sponsor@example.com'])
                ->get();

            foreach ($deputies as $index => $deputy) {
                $team->deputies()->attach($deputy->id, ['sort_order' => $index]);
            }
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
            $orgaTeam = OrgaTeam::query()->where('slug', 'lan-party-crew')->first();
            $aurora = Theme::query()->where('name', 'Aurora')->first();
            $sunset = Theme::query()->where('name', 'Sunset Arcade')->first();

            $events['published'] = Event::factory()->published()->create([
                'name' => 'Summer LAN 2026',
                'description' => 'The biggest LAN event of the year with tournaments, workshops, and fun.',
                'start_date' => '2026-07-15 16:00:00',
                'end_date' => '2026-07-18 14:00:00',
                'venue_id' => $venues->first()?->id,
                'orga_team_id' => $orgaTeam?->id,
                'theme_id' => $aurora?->id,
            ]);

            $events['draft'] = Event::factory()->create([
                'name' => 'Spring LAN 2026',
                'description' => 'Our annual spring LAN party with tournaments and fun.',
                'start_date' => '2026-04-10 18:00:00',
                'end_date' => '2026-04-12 16:00:00',
                'venue_id' => $venues->skip(1)->first()?->id,
                'orga_team_id' => $orgaTeam?->id,
            ]);

            $events['past'] = Event::factory()->published()->create([
                'name' => 'Winter LAN 2025',
                'description' => 'Last year\'s winter edition — a huge success.',
                'start_date' => '2025-12-27 14:00:00',
                'end_date' => '2025-12-29 18:00:00',
                'venue_id' => $venues->skip(2)->first()?->id,
                'orga_team_id' => $orgaTeam?->id,
                'theme_id' => $sunset?->id,
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
            }
        });

        return true;
    }

    /**
     * @param  array{published: Event, draft: Event, past: Event}  $events
     */
    private function seedSeatPlans(array $events): bool
    {
        if (SeatPlan::query()->whereIn('event_id', [
            $events['published']->id,
            $events['draft']->id,
            $events['past']->id,
        ])->exists()) {
            return false;
        }

        $this->components->task('Seeding seat plans', function () use ($events): void {
            // Hafenwerk Stralsund (industrial mid-size) — two blocks, 100 seats.
            SeatPlan::factory()->withBlocks([
                $this->buildGridBlock('block-1', 'Halle Nord', '#1f6feb', 10, 6, 0, 0),
                $this->buildGridBlock('block-2', 'Halle Süd', '#0ea5e9', 8, 5, 0, 260),
            ])->create([
                'name' => 'Hafenwerk – Hauptflächen',
                'event_id' => $events['published']->id,
            ]);

            // Campus Forum Greifswald (small flexible workshop space) — one block, 40 seats.
            SeatPlan::factory()->withBlocks([
                $this->buildGridBlock('block-1', 'Forum', '#22c55e', 8, 5, 0, 0),
            ])->create([
                'name' => 'Campus Forum – Workshop-Setup',
                'event_id' => $events['draft']->id,
            ]);

            // Nordlicht Messehalle Rostock (largest exhibition venue) — three blocks, 224 seats.
            SeatPlan::factory()->withBlocks([
                $this->buildGridBlock('block-1', 'Halle A', '#f97316', 12, 8, 0, 0),
                $this->buildGridBlock('block-2', 'Halle B', '#ef4444', 12, 8, 420, 0),
                $this->buildGridBlock('block-3', 'VIP Lounge', '#a855f7', 8, 4, 0, 320),
            ])->create([
                'name' => 'Nordlicht – Messeflächen',
                'event_id' => $events['past']->id,
            ]);
        });

        return true;
    }

    /**
     * Build a grid-shaped seat block for SeatPlanFactory::withBlocks().
     *
     * @return array{
     *     title: string,
     *     color: string,
     *     rows: list<array{name: string, sort_order: int, seats: list<array{number: int, title: string, x: int, y: int, salable: bool}>}>,
     *     labels: list<array{title: string, x: int, y: int, sort_order: int}>,
     * }
     */
    private function buildGridBlock(string $id, string $title, string $color, int $cols, int $rows, int $offsetX, int $offsetY): array
    {
        $pitch = 30;
        $rowsPayload = [];
        $labels = [];

        for ($row = 0; $row < $rows; $row++) {
            $rowLetter = chr(ord('A') + $row);

            $labels[] = [
                'title' => 'Row '.$rowLetter,
                'x' => $offsetX - $pitch,
                'y' => $offsetY + ($row * $pitch),
                'sort_order' => $row,
            ];

            $seats = [];
            for ($col = 0; $col < $cols; $col++) {
                $seats[] = [
                    'number' => $col + 1,
                    'title' => $rowLetter.($col + 1),
                    'x' => $offsetX + ($col * $pitch),
                    'y' => $offsetY + ($row * $pitch),
                    'salable' => true,
                ];
            }

            $rowsPayload[] = [
                'name' => $rowLetter,
                'sort_order' => $row,
                'seats' => $seats,
            ];
        }

        return [
            'title' => $title,
            'color' => $color,
            'rows' => $rowsPayload,
            'labels' => $labels,
        ];
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
    /**
     * Demo sponsors used by `db:seed-demo`.
     *
     * Logos live in `database/seeders/images/sponsors/demo-sponsor-{n}/` and are
     * AI-generated demo material (watermarked accordingly). The original
     * generation prompts are kept here for traceability.
     *
     * @return list<array{
     *     name: string,
     *     description: string,
     *     link: string,
     *     level: 'gold'|'silver'|'bronze',
     *     image_dir: string,
     *     logo_prompt: string
     * }>
     */
    private function demoSponsors(): array
    {
        return [
            [
                'name' => 'Northbyte Hosting',
                'description' => 'Northbyte Hosting ist ein fiktiver Infrastruktur- und Hosting-Partner für Community-Events, Turniere und moderne Webplattformen. Das Unternehmen positioniert sich als techniknaher Sponsor für dedizierte Server, Cloud-Ressourcen und zuverlässige Event-Infrastruktur.',
                'link' => 'https://lan-software.de',
                'level' => 'gold',
                'image_dir' => 'demo-sponsor-1',
                'logo_prompt' => 'A clean modern tech company logo for "Northbyte Hosting", minimalist geometric symbol, subtle connection to servers, cloud infrastructure and networking, professional B2B style, white background, flat vector design, dark blue and cyan color palette, no mockup, no 3D, no text outside the logo composition',
            ],
            [
                'name' => 'PixelForge Systems',
                'description' => 'PixelForge Systems ist ein fiktiver Technologie-Sponsor für Gaming-Hardware, Displays und Event-Arbeitsplätze. Die Marke steht für leistungsfähige Systeme, klare Formsprache und einen modernen, leicht gaming-nahen, aber professionellen Auftritt.',
                'link' => 'https://lan-software.de',
                'level' => 'silver',
                'image_dir' => 'demo-sponsor-2',
                'logo_prompt' => 'A sharp modern logo for "PixelForge Systems", combining a subtle pixel motif with a forged angular emblem, professional technology brand identity, flat vector design, white background, bold but clean, orange and graphite color palette, no mockup, no 3D effects, suitable for website header and sponsor wall',
            ],
            [
                'name' => 'Gournament',
                'description' => 'Gournament ist eine fiktive Softwarelösung zur Erstellung und Verwaltung von Turnierbäumen, entwickelt in Go. Die Plattform richtet sich an Veranstalter, E-Sport-Teams und Community-Projekte, die schnell und zuverlässig Brackets, Turnierstrukturen und Match-Abläufe organisieren möchten. Als Sponsor passt Gournament besonders gut in das LanCore-Ökosystem rund um Competitions und LanBrackets.',
                'link' => 'https://lan-software.de',
                'level' => 'bronze',
                'image_dir' => 'demo-sponsor-3',
                'logo_prompt' => 'A clean modern software logo for "Gournament", inspired by tournament brackets and the Go programming language ecosystem, minimalist geometric emblem, professional SaaS style, flat vector design, white background, teal and dark slate color palette, no mockup, no 3D, scalable for web and sponsor walls',
            ],
        ];
    }

    private function seedSponsors(array $events): bool
    {
        if (SponsorLevel::query()->where('name', 'Gold')->exists()) {
            return false;
        }

        $this->components->task('Seeding sponsors', function () use ($events): void {
            $levels = [
                'gold' => SponsorLevel::create(['name' => 'Gold', 'color' => '#FFD700', 'sort_order' => 0]),
                'silver' => SponsorLevel::create(['name' => 'Silver', 'color' => '#C0C0C0', 'sort_order' => 1]),
                'bronze' => SponsorLevel::create(['name' => 'Bronze', 'color' => '#CD7F32', 'sort_order' => 2]),
            ];

            $sponsors = [];

            foreach ($this->demoSponsors() as $data) {
                $logoPath = null;
                $sourceDir = database_path('seeders/images/sponsors/'.$data['image_dir']);
                $sources = glob($sourceDir.'/*.png') ?: [];
                if ($sources !== []) {
                    $logoPath = $this->copyDemoImageToStorage($sources[0], 'sponsors/logos');
                }

                $sponsors[] = Sponsor::factory()->create([
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'link' => $data['link'],
                    'logo' => $logoPath,
                    'sponsor_level_id' => $levels[$data['level']]->id,
                ]);
            }

            $publishedEvent = $events['published'];
            $publishedEvent->sponsors()->attach(collect($sponsors)->pluck('id')->all());

            $sponsorManager = User::query()->where('email', 'sponsor@example.com')->first();
            if ($sponsorManager) {
                $sponsors[0]->managers()->attach($sponsorManager->id);
                $sponsors[2]->managers()->attach($sponsorManager->id);
            }
        });

        return true;
    }

    /**
     * @param  array{published: Event, draft: Event, past: Event}  $events
     */
    private function seedCompetitions(array $events): bool
    {
        if (Competition::query()->where('name', 'CS2 Main Tournament')->exists()) {
            return false;
        }

        $this->components->task('Seeding competitions & teams', function () use ($events): void {
            $publishedEvent = $events['published'];

            $cs2 = Game::query()->where('slug', 'counter-strike-2')->first();
            $cs2Competitive = $cs2?->gameModes()->where('slug', '5v5-competitive')->first();

            $rocketLeague = Game::query()->where('slug', 'rocket-league')->first();
            $rl3v3 = $rocketLeague?->gameModes()->where('slug', '3v3-standard')->first();

            $tableTennis = Game::query()->where('slug', 'table-tennis')->first();
            $tt1v1 = $tableTennis?->gameModes()->where('slug', '1v1-singles')->first();

            $users = User::query()->where('email', '!=', 'superadmin@example.com')
                ->inRandomOrder()
                ->limit(40)
                ->get();

            // --- CS2 Main Tournament: 8 teams, single elimination, registration closed ---
            $cs2Tournament = Competition::create([
                'name' => 'CS2 Main Tournament',
                'slug' => 'cs2-main-tournament',
                'description' => 'The flagship Counter-Strike 2 tournament of Summer LAN 2026. 8 teams battle for glory in a single elimination bracket.',
                'event_id' => $publishedEvent->id,
                'game_id' => $cs2?->id,
                'game_mode_id' => $cs2Competitive?->id,
                'type' => CompetitionType::Tournament,
                'stage_type' => StageType::SingleElimination,
                'status' => CompetitionStatus::RegistrationClosed,
                'team_size' => 5,
                'max_teams' => 8,
                'registration_opens_at' => '2026-05-01 00:00:00',
                'registration_closes_at' => '2026-07-10 23:59:59',
                'starts_at' => '2026-07-15 18:00:00',
                'ends_at' => '2026-07-17 20:00:00',
                'settings' => ['result_submission_mode' => ResultSubmissionMode::ParticipantsWithProof->value],
            ]);

            $cs2TeamNames = [
                ['name' => 'Neon Vipers', 'tag' => 'NV'],
                ['name' => 'Digital Storm', 'tag' => 'DS'],
                ['name' => 'Frag Hunters', 'tag' => 'FH'],
                ['name' => 'Pixel Pirates', 'tag' => 'PP'],
                ['name' => 'Byte Force', 'tag' => 'BF'],
                ['name' => 'Shadow Ops', 'tag' => 'SO'],
                ['name' => 'Lag Lords', 'tag' => 'LL'],
                ['name' => 'Clutch Kings', 'tag' => 'CK'],
            ];

            $userIndex = 0;
            foreach ($cs2TeamNames as $teamData) {
                $captain = $users[$userIndex] ?? User::factory()->create();
                $team = CompetitionTeam::create([
                    'competition_id' => $cs2Tournament->id,
                    'name' => $teamData['name'],
                    'tag' => $teamData['tag'],
                    'captain_user_id' => $captain->id,
                ]);

                CompetitionTeamMember::create([
                    'team_id' => $team->id,
                    'user_id' => $captain->id,
                    'joined_at' => now()->subDays(rand(10, 30)),
                ]);

                for ($i = 1; $i < 5; $i++) {
                    $userIndex++;
                    $member = $users[$userIndex] ?? User::factory()->create();
                    CompetitionTeamMember::create([
                        'team_id' => $team->id,
                        'user_id' => $member->id,
                        'joined_at' => now()->subDays(rand(5, 25)),
                    ]);
                }

                $userIndex++;
            }

            // --- CS2 Wingman: 2v2, round robin, registration open ---
            $cs2Wingman = Competition::create([
                'name' => 'CS2 Wingman Cup',
                'slug' => 'cs2-wingman-cup',
                'description' => '2v2 Wingman side tournament. Casual fun between main matches.',
                'event_id' => $publishedEvent->id,
                'game_id' => $cs2?->id,
                'game_mode_id' => $cs2?->gameModes()->where('slug', '2v2-wingman')->first()?->id,
                'type' => CompetitionType::Tournament,
                'stage_type' => StageType::RoundRobin,
                'status' => CompetitionStatus::RegistrationOpen,
                'team_size' => 2,
                'max_teams' => 8,
                'registration_opens_at' => '2026-06-01 00:00:00',
                'registration_closes_at' => '2026-07-14 23:59:59',
                'starts_at' => '2026-07-16 10:00:00',
                'ends_at' => '2026-07-16 18:00:00',
            ]);

            $wingmanTeams = [
                ['name' => 'Double Trouble', 'tag' => 'DT'],
                ['name' => 'Two of a Kind', 'tag' => '2K'],
                ['name' => 'Duo Queue', 'tag' => 'DQ'],
            ];

            foreach ($wingmanTeams as $teamData) {
                $captain = User::factory()->create();
                $team = CompetitionTeam::create([
                    'competition_id' => $cs2Wingman->id,
                    'name' => $teamData['name'],
                    'tag' => $teamData['tag'],
                    'captain_user_id' => $captain->id,
                ]);

                CompetitionTeamMember::create([
                    'team_id' => $team->id,
                    'user_id' => $captain->id,
                    'joined_at' => now()->subDays(rand(3, 14)),
                ]);

                $partner = User::factory()->create();
                CompetitionTeamMember::create([
                    'team_id' => $team->id,
                    'user_id' => $partner->id,
                    'joined_at' => now()->subDays(rand(1, 10)),
                ]);
            }

            // --- Rocket League: 3v3, group stage, draft ---
            Competition::create([
                'name' => 'Rocket League Showdown',
                'slug' => 'rocket-league-showdown',
                'description' => '3v3 Rocket League tournament with group stage into single elimination playoffs.',
                'event_id' => $publishedEvent->id,
                'game_id' => $rocketLeague?->id,
                'game_mode_id' => $rl3v3?->id,
                'type' => CompetitionType::Tournament,
                'stage_type' => StageType::GroupStage,
                'status' => CompetitionStatus::Draft,
                'team_size' => 3,
                'max_teams' => 6,
                'starts_at' => '2026-07-17 10:00:00',
                'ends_at' => '2026-07-17 18:00:00',
            ]);

            // --- Table Tennis: 1v1, single elimination, registration open ---
            $ttTournament = Competition::create([
                'name' => 'Table Tennis Championship',
                'slug' => 'table-tennis-championship',
                'description' => '1v1 single elimination table tennis. Take a break from the screen!',
                'event_id' => $publishedEvent->id,
                'game_id' => $tableTennis?->id,
                'game_mode_id' => $tt1v1?->id,
                'type' => CompetitionType::Tournament,
                'stage_type' => StageType::SingleElimination,
                'status' => CompetitionStatus::RegistrationOpen,
                'team_size' => 1,
                'max_teams' => 16,
                'registration_opens_at' => '2026-06-15 00:00:00',
                'registration_closes_at' => '2026-07-15 12:00:00',
                'starts_at' => '2026-07-16 10:00:00',
                'ends_at' => '2026-07-16 14:00:00',
            ]);

            $soloPlayers = User::query()->inRandomOrder()->limit(6)->get();
            foreach ($soloPlayers as $player) {
                $team = CompetitionTeam::create([
                    'competition_id' => $ttTournament->id,
                    'name' => $player->name,
                    'tag' => null,
                    'captain_user_id' => $player->id,
                ]);

                CompetitionTeamMember::create([
                    'team_id' => $team->id,
                    'user_id' => $player->id,
                    'joined_at' => now()->subDays(rand(1, 14)),
                ]);
            }

            // --- Past event: finished competition ---
            $pastEvent = $events['past'];
            Competition::create([
                'name' => 'Winter LAN CS2 Cup',
                'slug' => 'winter-lan-cs2-cup',
                'description' => 'The CS2 tournament from Winter LAN 2025. What a final!',
                'event_id' => $pastEvent->id,
                'game_id' => $cs2?->id,
                'game_mode_id' => $cs2Competitive?->id,
                'type' => CompetitionType::Tournament,
                'stage_type' => StageType::SingleElimination,
                'status' => CompetitionStatus::Finished,
                'team_size' => 5,
                'max_teams' => 4,
                'registration_opens_at' => '2025-12-01 00:00:00',
                'registration_closes_at' => '2025-12-26 23:59:59',
                'starts_at' => '2025-12-27 16:00:00',
                'ends_at' => '2025-12-28 20:00:00',
            ]);
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

    private function seedAchievements(): bool
    {
        if (Achievement::query()->where('name', 'Welcome Aboard')->exists()) {
            return false;
        }

        $this->components->task('Seeding achievements', function (): void {
            $achievements = [
                [
                    'name' => 'Welcome Aboard',
                    'description' => 'Created your account and joined the community.',
                    'notification_text' => 'You\'ve earned the Welcome Aboard achievement!',
                    'color' => '#6366f1',
                    'icon' => 'star',
                    'events' => [GrantableEvent::UserRegistered],
                ],
                [
                    'name' => 'News Junkie',
                    'description' => 'Read your first news article.',
                    'notification_text' => 'You read a news article and unlocked News Junkie!',
                    'color' => '#0ea5e9',
                    'icon' => 'zap',
                    'events' => [GrantableEvent::NewsArticleRead],
                ],
                [
                    'name' => 'In the Loop',
                    'description' => 'Browsed the announcements page.',
                    'notification_text' => 'You stayed informed and earned In the Loop!',
                    'color' => '#f59e0b',
                    'icon' => 'bell',
                    'events' => [GrantableEvent::AnnouncementsViewed],
                ],
                [
                    'name' => 'Clean Slate',
                    'description' => 'Archived all your notifications.',
                    'notification_text' => 'Inbox zero achieved — you earned Clean Slate!',
                    'color' => '#10b981',
                    'icon' => 'shield',
                    'events' => [GrantableEvent::NotificationsArchived],
                ],
                [
                    'name' => 'Personalized',
                    'description' => 'Updated your notification preferences.',
                    'notification_text' => 'Your preferences are set — Personalized unlocked!',
                    'color' => '#8b5cf6',
                    'icon' => 'settings',
                    'events' => [GrantableEvent::NotificationPreferencesUpdated],
                ],
                [
                    'name' => 'Identity Crisis',
                    'description' => 'Updated your profile information.',
                    'notification_text' => 'You updated your profile and earned Identity Crisis!',
                    'color' => '#ec4899',
                    'icon' => 'user',
                    'events' => [GrantableEvent::ProfileUpdated],
                ],
                [
                    'name' => 'Seat Keeper',
                    'description' => 'Configured your Advanced Seating / ticket discovery settings.',
                    'notification_text' => 'You configured your seating settings — Seat Keeper unlocked!',
                    'color' => '#f97316',
                    'icon' => 'map-pin',
                    'events' => [GrantableEvent::TicketDiscoverySettingsUpdated],
                ],
                [
                    'name' => 'Ticket Holder',
                    'description' => 'Purchased a ticket for a LAN event.',
                    'notification_text' => 'You\'re going to the event — Ticket Holder unlocked!',
                    'color' => '#14b8a6',
                    'icon' => 'ticket',
                    'events' => [GrantableEvent::TicketPurchased],
                ],
                [
                    'name' => 'Window Shopper',
                    'description' => 'Added an item to your cart.',
                    'notification_text' => 'You added something to your cart — Window Shopper unlocked!',
                    'color' => '#84cc16',
                    'icon' => 'shopping-cart',
                    'events' => [GrantableEvent::CartItemAdded],
                ],
                [
                    'name' => 'Connected',
                    'description' => 'Accessed an external integration via SSO.',
                    'notification_text' => 'You used an integration and earned Connected!',
                    'color' => '#64748b',
                    'icon' => 'link',
                    'events' => [GrantableEvent::IntegrationAccessed],
                ],
                [
                    'name' => 'Event Watcher',
                    'description' => 'Witnessed a new event go live.',
                    'notification_text' => 'A new event launched — you earned Event Watcher!',
                    'color' => '#a855f7',
                    'icon' => 'calendar',
                    'events' => [GrantableEvent::EventPublished],
                ],
                [
                    'name' => 'Rising Star',
                    'description' => 'Had your role changed — you\'re moving up!',
                    'notification_text' => 'Your role changed — Rising Star unlocked!',
                    'color' => '#fbbf24',
                    'icon' => 'crown',
                    'events' => [GrantableEvent::UserRolesChanged],
                ],
                [
                    'name' => 'Early Bird',
                    'description' => 'Read a news article and checked announcements — you\'re always first.',
                    'notification_text' => 'You read the news AND checked announcements — Early Bird earned!',
                    'color' => '#f43f5e',
                    'icon' => 'flame',
                    'events' => [GrantableEvent::NewsArticleRead, GrantableEvent::AnnouncementsViewed],
                ],
                [
                    'name' => 'Completionist',
                    'description' => 'Updated your profile and configured your notification preferences.',
                    'notification_text' => 'Profile and preferences done — Completionist unlocked!',
                    'color' => '#06b6d4',
                    'icon' => 'award',
                    'events' => [GrantableEvent::ProfileUpdated, GrantableEvent::NotificationPreferencesUpdated],
                ],
            ];

            foreach ($achievements as $data) {
                $achievement = Achievement::create([
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'notification_text' => $data['notification_text'],
                    'color' => $data['color'],
                    'icon' => $data['icon'],
                    'is_active' => true,
                ]);

                foreach ($data['events'] as $grantableEvent) {
                    $achievement->achievementEvents()->create(['event_class' => $grantableEvent->value]);
                }
            }
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

            Webhook::factory()->create([
                'name' => 'MockServer — Ticket Purchased',
                'description' => 'Development webhook that forwards ticket purchased events to MockServer for inspection.',
                'url' => 'http://mockserver:1080/webhooks/ticket-purchased',
                'event' => WebhookEvent::TicketPurchased,
                'secret' => null,
                'is_active' => true,
            ]);

            Webhook::factory()->create([
                'name' => 'MockServer — Profile Updated',
                'description' => 'Development webhook that forwards profile updated events to MockServer for inspection.',
                'url' => 'http://mockserver:1080/webhooks/profile-updated',
                'event' => WebhookEvent::ProfileUpdated,
                'secret' => null,
                'is_active' => true,
            ]);

            Webhook::factory()->create([
                'name' => 'MockServer — Integration Accessed',
                'description' => 'Development webhook that forwards integration accessed events to MockServer for inspection.',
                'url' => 'http://mockserver:1080/webhooks/integration-accessed',
                'event' => WebhookEvent::IntegrationAccessed,
                'secret' => null,
                'is_active' => true,
            ]);
        });

        return true;
    }

    private function seedDemoAnnouncement(): bool
    {
        $marker = 'Welcome to the Lan-Software public demo!';

        if (! config('app.demo')) {
            Announcement::query()->where('title', $marker)->delete();

            return false;
        }

        if (Announcement::query()->where('title', $marker)->exists()) {
            return false;
        }

        $author = User::query()->where('email', 'admin@example.com')->first()
            ?? User::query()->whereNotNull('email')->first();

        if ($author === null) {
            return false;
        }

        $this->components->task('Seeding demo announcement', function () use ($marker, $author): void {
            Announcement::query()->create([
                'title' => $marker,
                'description' => 'This environment resets hourly. Feel free to explore — any changes will be wiped on the next reset.',
                'priority' => AnnouncementPriority::Normal,
                'audience' => AnnouncementAudience::All,
                'event_id' => null,
                'author_id' => $author->id,
                'published_at' => now(),
            ]);
        });

        return true;
    }

    private function seedShopConditions(): bool
    {
        if (GlobalPurchaseCondition::query()->where('name', 'Terms of Service')->exists()) {
            return false;
        }

        $this->components->task('Seeding purchase conditions & requirements', function (): void {
            // --- Global Purchase Conditions (apply to all orders) ---
            GlobalPurchaseCondition::create([
                'name' => 'Terms of Service',
                'description' => 'General terms and conditions for all ticket purchases.',
                'content' => "By purchasing a ticket you agree to the following terms:\n\n"
                    ."1. **No Refunds** — All ticket sales are final. Tickets cannot be refunded after purchase.\n"
                    ."2. **Transferability** — Tickets may be transferred to another person via the ticket transfer feature. The new holder must have a LanCore account.\n"
                    ."3. **Code of Conduct** — All attendees must follow the event's Code of Conduct. Violations may result in removal without refund.\n"
                    ."4. **Liability** — The organizer is not responsible for personal belongings. Bring your own locks and keep valuables secure.\n"
                    .'5. **Photography** — Event photography and videography will take place. By attending, you consent to being photographed.',
                'acknowledgement_label' => 'I accept the Terms of Service',
                'is_required' => true,
                'is_active' => true,
                'requires_scroll' => true,
                'sort_order' => 0,
            ]);

            GlobalPurchaseCondition::create([
                'name' => 'Age Verification',
                'description' => 'Confirm minimum age requirement for the event.',
                'content' => 'This event is open to participants aged 16 and older. Participants under 18 must have a signed parental consent form submitted before check-in.',
                'acknowledgement_label' => 'I confirm that I am at least 16 years old',
                'is_required' => true,
                'is_active' => true,
                'requires_scroll' => false,
                'sort_order' => 1,
            ]);

            GlobalPurchaseCondition::create([
                'name' => 'Newsletter Subscription',
                'description' => 'Optional newsletter sign-up during checkout.',
                'content' => 'Stay up to date with future events, early-bird offers, and community news. You can unsubscribe at any time.',
                'acknowledgement_label' => 'I would like to receive the event newsletter',
                'is_required' => false,
                'is_active' => true,
                'requires_scroll' => false,
                'sort_order' => 2,
            ]);

            // --- Payment Provider Conditions ---
            PaymentProviderCondition::create([
                'payment_method' => PaymentMethod::Stripe,
                'name' => 'Credit Card Payment Terms',
                'description' => 'Terms specific to credit card payments via Stripe.',
                'content' => "Payment is processed securely via Stripe. Your card will be charged immediately upon purchase.\n\n"
                    ."- We do not store your full card details. All payment data is handled by Stripe.\n"
                    ."- A payment confirmation will be sent to your email address.\n"
                    .'- For chargebacks or disputes, contact us before contacting your bank.',
                'acknowledgement_label' => 'I agree to the credit card payment terms',
                'is_required' => true,
                'is_active' => true,
                'requires_scroll' => false,
                'sort_order' => 0,
            ]);

            PaymentProviderCondition::create([
                'payment_method' => PaymentMethod::OnSite,
                'name' => 'On-Site Payment Terms',
                'description' => 'Terms for paying at the event venue.',
                'content' => "On-site payment reserves your ticket but does not guarantee it. Your reservation is held for 48 hours.\n\n"
                    ."- Payment must be made in cash at the check-in desk.\n"
                    ."- If payment is not received within 48 hours, your reservation may be released.\n"
                    .'- On-site payments do not include online payment processing fees.',
                'acknowledgement_label' => 'I understand the on-site payment terms',
                'is_required' => true,
                'is_active' => true,
                'requires_scroll' => false,
                'sort_order' => 0,
            ]);

            // --- Purchase Requirements (linked to specific ticket types) ---
            $premiumReq = PurchaseRequirement::create([
                'name' => 'Premium Seat Agreement',
                'description' => 'Additional requirements for premium ticket holders.',
                'requirements_content' => "Premium ticket holders receive priority seating in the front rows with extra desk space and dedicated power strips.\n\n"
                    ."Please note:\n"
                    ."- Premium seats are assigned first-come, first-served within the premium zone.\n"
                    ."- Seat swaps within the premium zone must be coordinated with staff.\n"
                    .'- The premium zone has a noise limit policy — no open speakers.',
                'acknowledgements' => [
                    'I understand the premium zone rules',
                    'I agree to the noise limit policy',
                ],
                'is_active' => true,
                'requires_scroll' => false,
            ]);

            $clanReq = PurchaseRequirement::create([
                'name' => 'Clan Row Requirements',
                'description' => 'Requirements for purchasing clan row tickets.',
                'requirements_content' => "Clan row tickets provide a contiguous block of seats for your group.\n\n"
                    ."Requirements:\n"
                    ."- Minimum 4 tickets must be purchased together for a clan row.\n"
                    ."- All clan members must check in within the first 2 hours of the event.\n"
                    .'- Unclaimed seats after 2 hours may be reassigned.',
                'acknowledgements' => [
                    'I confirm I am purchasing for a group of at least 4',
                    'I understand the check-in deadline policy',
                ],
                'is_active' => true,
                'requires_scroll' => false,
            ]);

            // Link requirements to ticket types
            $premiumTicket = TicketType::query()->where('name', 'Premium Ticket')->first();
            $vipTicket = TicketType::query()->where('name', 'VIP Ticket')->first();
            $clanTicket = TicketType::query()->where('name', 'Clan Row Ticket')->first();

            if ($premiumTicket) {
                $premiumReq->ticketTypes()->syncWithoutDetaching([$premiumTicket->id]);
            }
            if ($vipTicket) {
                $premiumReq->ticketTypes()->syncWithoutDetaching([$vipTicket->id]);
            }
            if ($clanTicket) {
                $clanReq->ticketTypes()->syncWithoutDetaching([$clanTicket->id]);
            }
        });

        return true;
    }

    private function seedOrganization(): bool
    {
        if (OrganizationSetting::get('name') !== null) {
            return false;
        }

        $this->components->task('Seeding organization settings', function (): void {
            $settings = [
                'name' => 'LAN Party e.V.',
                'address_line1' => 'Musterstraße 42',
                'address_line2' => '12345 Berlin, Germany',
                'email' => 'info@lanparty.example.com',
                'phone' => '+49 30 123 456 78',
                'website' => 'https://lanparty.example.com',
                'tax_id' => 'DE123456789',
                'registration_id' => 'VR 12345, Amtsgericht Berlin-Charlottenburg',
                'legal_notice' => 'All prices include applicable taxes. Tickets are non-refundable unless otherwise stated.',
            ];

            foreach ($settings as $key => $value) {
                OrganizationSetting::set($key, $value);
            }

            // Invoice config
            ShopSetting::set('invoice_prefix', 'LAN-');
            ShopSetting::set('invoice_notes', "Payment is due upon receipt.\nFor questions about your order, contact us at info@lanparty.example.com.");
            ShopSetting::set('invoice_footer', 'Thank you for attending our LAN party!');
        });

        return true;
    }
}
