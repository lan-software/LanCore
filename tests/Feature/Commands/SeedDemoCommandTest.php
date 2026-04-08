<?php

use App\Domain\Event\Models\Event;
use App\Domain\Games\Models\Game;
use App\Domain\Games\Models\GameMode;
use App\Domain\News\Models\NewsArticle;
use App\Domain\Program\Models\Program;
use App\Domain\Program\Models\TimeSlot;
use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Sponsoring\Models\Sponsor;
use App\Domain\Sponsoring\Models\SponsorLevel;
use App\Domain\Ticketing\Models\Addon;
use App\Domain\Ticketing\Models\TicketCategory;
use App\Domain\Ticketing\Models\TicketType;
use App\Domain\Venue\Models\Venue;
use App\Models\Role;
use App\Models\User;

it('seeds demo data successfully', function () {
    $this->artisan('db:seed-demo')
        ->assertSuccessful();

    expect(Role::count())->toBe(5)
        ->and(User::count())->toBeGreaterThanOrEqual(24)
        ->and(Venue::count())->toBe(3)
        ->and(Game::count())->toBe(5)
        ->and(GameMode::count())->toBe(8)
        ->and(Event::count())->toBe(3)
        ->and(TicketType::count())->toBeGreaterThanOrEqual(8)
        ->and(TicketCategory::count())->toBeGreaterThanOrEqual(2)
        ->and(Addon::count())->toBeGreaterThanOrEqual(6)
        ->and(SeatPlan::count())->toBeGreaterThanOrEqual(2)
        ->and(Program::count())->toBeGreaterThanOrEqual(3)
        ->and(TimeSlot::count())->toBeGreaterThanOrEqual(6)
        ->and(SponsorLevel::count())->toBe(3)
        ->and(Sponsor::count())->toBe(3)
        ->and(NewsArticle::count())->toBe(3);
});

it('creates test users with correct roles', function () {
    $this->artisan('db:seed-demo')
        ->assertSuccessful();

    expect(User::where('email', 'user@example.com')->exists())->toBeTrue()
        ->and(User::where('email', 'admin@example.com')->exists())->toBeTrue()
        ->and(User::where('email', 'superadmin@example.com')->exists())->toBeTrue()
        ->and(User::where('email', 'sponsor@example.com')->exists())->toBeTrue();
});

it('also runs the default database seeder', function () {
    $this->artisan('db:seed-demo')
        ->assertSuccessful();

    expect(Role::count())->toBe(5);
});
