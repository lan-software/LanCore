<?php

use App\Domain\Event\Models\Event;
use App\Domain\Notification\Notifications\SeatAssignmentInvalidatedNotification;
use App\Domain\Seating\Models\SeatAssignment;
use App\Domain\Seating\Models\SeatPlan;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketType;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    $this->admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->event = Event::factory()->create();
    $this->type = TicketType::factory()->create([
        'event_id' => $this->event->id,
        'max_users_per_ticket' => 1,
    ]);

    $this->plan = SeatPlan::factory()->create([
        'event_id' => $this->event->id,
        'data' => ['blocks' => [
            ['id' => 'a', 'title' => 'A', 'color' => '#fff', 'seats' => [
                ['id' => 'A1', 'title' => 'A1', 'x' => 0, 'y' => 0, 'salable' => true],
                ['id' => 'A2', 'title' => 'A2', 'x' => 1, 'y' => 0, 'salable' => true],
            ]],
        ]],
    ]);

    $this->ticketOwner = User::factory()->create();
    $this->ticket = Ticket::factory()->create([
        'event_id' => $this->event->id,
        'ticket_type_id' => $this->type->id,
        'owner_id' => $this->ticketOwner->id,
    ]);
    SeatAssignment::factory()->create([
        'ticket_id' => $this->ticket->id,
        'user_id' => $this->ticketOwner->id,
        'seat_plan_id' => $this->plan->id,
        'seat_id' => 'A1',
    ]);
});

it('reports invalidations without writing when confirm_invalidations is false', function (): void {
    // New data removes seat A1.
    $newData = json_encode(['blocks' => [
        ['id' => 'a', 'title' => 'A', 'color' => '#fff', 'seats' => [
            ['id' => 'A2', 'title' => 'A2', 'x' => 1, 'y' => 0, 'salable' => true],
        ]],
    ]]);

    $this->actingAs($this->admin)
        ->patch("/seat-plans/{$this->plan->id}", [
            'name' => $this->plan->name,
            'data' => $newData,
        ])
        ->assertRedirect()
        ->assertSessionHas('invalidations');

    // DB unchanged.
    expect($this->plan->fresh()->data['blocks'][0]['seats'])->toHaveCount(2)
        ->and(SeatAssignment::query()->count())->toBe(1);
});

it('persists + releases + notifies when confirm_invalidations is true', function (): void {
    Notification::fake();

    $newData = json_encode(['blocks' => [
        ['id' => 'a', 'title' => 'A', 'color' => '#fff', 'seats' => [
            ['id' => 'A2', 'title' => 'A2', 'x' => 1, 'y' => 0, 'salable' => true],
        ]],
    ]]);

    $this->actingAs($this->admin)
        ->patch("/seat-plans/{$this->plan->id}", [
            'name' => $this->plan->name,
            'data' => $newData,
            'confirm_invalidations' => true,
        ])
        ->assertRedirect()
        ->assertSessionHas('status', 'seat-plan-updated');

    expect($this->plan->fresh()->data['blocks'][0]['seats'])->toHaveCount(1)
        ->and(SeatAssignment::query()->count())->toBe(0);

    Notification::assertSentTo(
        $this->ticketOwner,
        SeatAssignmentInvalidatedNotification::class,
    );
});

it('requires the ManageSeating (or SeatPlanPolicy::update) permission', function (): void {
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->patch("/seat-plans/{$this->plan->id}", [
            'name' => $this->plan->name,
            'data' => json_encode($this->plan->data),
        ])
        ->assertForbidden();
});
