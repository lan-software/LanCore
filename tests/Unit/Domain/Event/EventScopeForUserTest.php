<?php

use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\CompetitionTeam;
use App\Domain\Competition\Models\CompetitionTeamMember;
use App\Domain\Event\Models\Event;
use App\Domain\Shop\Models\Order;
use App\Domain\Ticketing\Models\Ticket;
use App\Models\User;

it('returns events where the user owns a ticket', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    Ticket::factory()->create(['event_id' => $event->id, 'owner_id' => $user->id]);

    $ids = Event::query()->forUser($user)->pluck('id')->all();

    expect($ids)->toContain($event->id);
});

it('returns events where the user manages a ticket', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    Ticket::factory()->create([
        'event_id' => $event->id,
        'owner_id' => User::factory()->create()->id,
        'manager_id' => $user->id,
    ]);

    $ids = Event::query()->forUser($user)->pluck('id')->all();

    expect($ids)->toContain($event->id);
});

it('returns events where the user is attached to a ticket via the pivot', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $ticket = Ticket::factory()->create([
        'event_id' => $event->id,
        'owner_id' => User::factory()->create()->id,
    ]);
    $ticket->users()->attach($user->id);

    $ids = Event::query()->forUser($user)->pluck('id')->all();

    expect($ids)->toContain($event->id);
});

it('returns events where the user is an active competition team member with competition.event_id set', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $competition = Competition::factory()->create(['event_id' => $event->id]);
    $team = CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
        'captain_user_id' => $user->id,
    ]);
    CompetitionTeamMember::factory()->create([
        'team_id' => $team->id,
        'user_id' => $user->id,
        'joined_at' => now()->subDay(),
    ]);

    $ids = Event::query()->forUser($user)->pluck('id')->all();

    expect($ids)->toContain($event->id);
});

it('returns events where the user has an order with non-null event_id', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    Order::factory()->create(['user_id' => $user->id, 'event_id' => $event->id]);

    $ids = Event::query()->forUser($user)->pluck('id')->all();

    expect($ids)->toContain($event->id);
});

it('does not return events the user has no participation in', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    // Other user owns a ticket so the event exists but user has no link
    Ticket::factory()->create(['event_id' => $event->id, 'owner_id' => User::factory()->create()->id]);

    $ids = Event::query()->forUser($user)->pluck('id')->all();

    expect($ids)->not->toContain($event->id);
});

it('does not return events for team memberships that have been left (left_at is set)', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $competition = Competition::factory()->create(['event_id' => $event->id]);
    $team = CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
        'captain_user_id' => $user->id,
    ]);
    CompetitionTeamMember::factory()->create([
        'team_id' => $team->id,
        'user_id' => $user->id,
        'joined_at' => now()->subDays(2),
        'left_at' => now()->subDay(),
    ]);

    $ids = Event::query()->forUser($user)->pluck('id')->all();

    expect($ids)->not->toContain($event->id);
});

it('does not return events for competitions whose event_id is null', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();
    $competition = Competition::factory()->create(['event_id' => null]);
    $team = CompetitionTeam::factory()->create([
        'competition_id' => $competition->id,
        'captain_user_id' => $user->id,
    ]);
    CompetitionTeamMember::factory()->create([
        'team_id' => $team->id,
        'user_id' => $user->id,
        'joined_at' => now()->subDay(),
    ]);

    $ids = Event::query()->forUser($user)->pluck('id')->all();

    expect($ids)->not->toContain($event->id);
});

it('does not return events solely because another user has an order on them', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $event = Event::factory()->create();
    Order::factory()->create(['user_id' => $other->id, 'event_id' => $event->id]);

    $ids = Event::query()->forUser($user)->pluck('id')->all();

    expect($ids)->not->toContain($event->id);
});
