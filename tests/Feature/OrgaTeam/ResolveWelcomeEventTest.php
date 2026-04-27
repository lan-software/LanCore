<?php

use App\Domain\Event\Enums\EventStatus;
use App\Domain\Event\Models\Event;
use App\Domain\OrgaTeam\Actions\ResolveWelcomeEvent;

beforeEach(function () {
    $this->action = new ResolveWelcomeEvent;
});

it('returns null when no published upcoming event exists', function () {
    Event::factory()->create([
        'status' => EventStatus::Draft,
        'start_date' => now()->addDay(),
    ]);
    Event::factory()->create([
        'status' => EventStatus::Published,
        'start_date' => now()->subDay(),
        'end_date' => now()->subHour(),
    ]);

    expect($this->action->execute())->toBeNull();
});

it('returns the soonest published upcoming event', function () {
    $further = Event::factory()->create([
        'status' => EventStatus::Published,
        'start_date' => now()->addWeeks(2),
        'end_date' => now()->addWeeks(2)->addDay(),
    ]);
    $sooner = Event::factory()->create([
        'status' => EventStatus::Published,
        'start_date' => now()->addDays(3),
        'end_date' => now()->addDays(4),
    ]);

    $resolved = $this->action->execute();

    expect($resolved)->not->toBeNull();
    expect($resolved->id)->toBe($sooner->id);
    expect($resolved->id)->not->toBe($further->id);
});

it('excludes draft events even if upcoming', function () {
    Event::factory()->create([
        'status' => EventStatus::Draft,
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(2),
    ]);

    expect($this->action->execute())->toBeNull();
});
