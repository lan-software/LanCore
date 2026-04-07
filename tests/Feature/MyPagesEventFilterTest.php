<?php

use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\CompetitionTeam;
use App\Domain\Competition\Models\CompetitionTeamMember;
use App\Domain\Event\Models\Event;
use App\Domain\Shop\Models\Order;
use App\Domain\Ticketing\Models\Ticket;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
});

/**
 * Helper to seed a user with one competition+team+order+ticket per event,
 * across two events.
 *
 * @return array{user: User, eventA: Event, eventB: Event}
 */
function seedMyPagesData(): array
{
    $user = User::factory()->withRole(RoleName::User)->create();
    $eventA = Event::factory()->create();
    $eventB = Event::factory()->create();

    foreach ([$eventA, $eventB] as $event) {
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
        Order::factory()->create(['user_id' => $user->id, 'event_id' => $event->id]);
        Ticket::factory()->create(['event_id' => $event->id, 'owner_id' => $user->id]);
    }

    return ['user' => $user, 'eventA' => $eventA, 'eventB' => $eventB];
}

it('filters my-competitions by my_selected_event_id', function () {
    ['user' => $user, 'eventA' => $eventA] = seedMyPagesData();

    $this->actingAs($user)
        ->withSession(['my_selected_event_id' => $eventA->id])
        ->get('/my-competitions')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->where('competitions.total', 1));
});

it('returns all my-competitions when no my_selected_event_id is set', function () {
    ['user' => $user] = seedMyPagesData();

    $this->actingAs($user)
        ->get('/my-competitions')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->where('competitions.total', 2));
});

it('filters my-teams by my_selected_event_id via competition.event_id', function () {
    ['user' => $user, 'eventA' => $eventA] = seedMyPagesData();

    $this->actingAs($user)
        ->withSession(['my_selected_event_id' => $eventA->id])
        ->get('/my-teams')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('teams', 1));
});

it('returns all my-teams when no my_selected_event_id is set', function () {
    ['user' => $user] = seedMyPagesData();

    $this->actingAs($user)
        ->get('/my-teams')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('teams', 2));
});

it('filters my-orders by my_selected_event_id', function () {
    ['user' => $user, 'eventA' => $eventA] = seedMyPagesData();

    $this->actingAs($user)
        ->withSession(['my_selected_event_id' => $eventA->id])
        ->get('/my-orders')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('orders', 1));
});

it('returns all my-orders when no my_selected_event_id is set', function () {
    ['user' => $user] = seedMyPagesData();

    $this->actingAs($user)
        ->get('/my-orders')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('orders', 2));
});

it('filters owned tickets index by my_selected_event_id', function () {
    ['user' => $user, 'eventA' => $eventA] = seedMyPagesData();

    $this->actingAs($user)
        ->withSession(['my_selected_event_id' => $eventA->id])
        ->get('/tickets')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('ownedTickets', 1));
});

it('returns all owned tickets when no my_selected_event_id is set', function () {
    ['user' => $user] = seedMyPagesData();

    $this->actingAs($user)
        ->get('/tickets')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('ownedTickets', 2));
});
