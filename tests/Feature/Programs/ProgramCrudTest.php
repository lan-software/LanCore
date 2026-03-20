<?php

use App\Domain\Event\Models\Event;
use App\Domain\Program\Models\Program;
use App\Domain\Program\Models\TimeSlot;
use App\Domain\Sponsoring\Models\Sponsor;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
    Role::updateOrCreate(['name' => RoleName::Admin->value], ['label' => 'Admin']);
    Role::updateOrCreate(['name' => RoleName::Superadmin->value], ['label' => 'Superadmin']);
});

it('allows admins to view the create program page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get('/programs/create')
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('programs/Create')
                ->has('events')
        );
});

it('allows admins to store a new program', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->post('/programs', [
            'name' => 'Main Schedule',
            'description' => 'The main event schedule.',
            'visibility' => 'public',
            'event_id' => $event->id,
        ])
        ->assertRedirect('/programs');

    expect(Program::where('name', 'Main Schedule')->exists())->toBeTrue();
});

it('allows admins to store a program with time slots', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->post('/programs', [
            'name' => 'Tournament',
            'visibility' => 'public',
            'event_id' => $event->id,
            'time_slots' => [
                [
                    'name' => 'Registration',
                    'starts_at' => '2026-07-01 09:00:00',
                    'visibility' => 'public',
                ],
                [
                    'name' => 'Group Stage',
                    'starts_at' => '2026-07-01 10:00:00',
                    'visibility' => 'public',
                ],
            ],
        ])
        ->assertRedirect('/programs');

    $program = Program::where('name', 'Tournament')->first();
    expect($program)->not->toBeNull();
    expect($program->timeSlots)->toHaveCount(2);
});

it('marks a program as primary for the event', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->post('/programs', [
            'name' => 'Primary Program',
            'visibility' => 'public',
            'event_id' => $event->id,
            'is_primary' => true,
        ])
        ->assertRedirect('/programs');

    $program = Program::where('name', 'Primary Program')->first();
    expect($event->fresh()->primary_program_id)->toBe($program->id);
});

it('validates required fields when storing a program', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->post('/programs', [])
        ->assertSessionHasErrors(['name', 'visibility', 'event_id']);
});

it('allows admins to view the edit program page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $program = Program::factory()->create();

    $this->actingAs($admin)
        ->get("/programs/{$program->id}")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('programs/Edit')
                ->has('program')
                ->has('events')
                ->where('program.id', $program->id)
        );
});

it('allows admins to update a program', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $program = Program::factory()->create();

    $this->actingAs($admin)
        ->patch("/programs/{$program->id}", [
            'name' => 'Updated Program Name',
            'visibility' => 'internal',
        ])
        ->assertRedirect();

    expect($program->fresh())
        ->name->toBe('Updated Program Name')
        ->visibility->value->toBe('internal');
});

it('allows admins to update time slots on a program', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $program = Program::factory()->create();
    $slot = TimeSlot::factory()->for($program)->create(['name' => 'Old Slot']);

    $this->actingAs($admin)
        ->patch("/programs/{$program->id}", [
            'name' => $program->name,
            'visibility' => $program->visibility->value,
            'time_slots' => [
                [
                    'id' => $slot->id,
                    'name' => 'Updated Slot',
                    'starts_at' => '2026-07-01 11:00:00',
                    'visibility' => 'public',
                ],
                [
                    'name' => 'New Slot',
                    'starts_at' => '2026-07-01 14:00:00',
                    'visibility' => 'internal',
                ],
            ],
        ])
        ->assertRedirect();

    expect($program->fresh()->timeSlots)->toHaveCount(2);
    expect($slot->fresh()->name)->toBe('Updated Slot');
});

it('allows admins to delete a program', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $program = Program::factory()->create();

    $this->actingAs($admin)
        ->delete("/programs/{$program->id}")
        ->assertRedirect('/programs');

    expect(Program::find($program->id))->toBeNull();
});

it('nullifies primary_program_id when deleting a primary program', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $event = Event::factory()->create();
    $program = Program::factory()->for($event)->create();
    $event->update(['primary_program_id' => $program->id]);

    $this->actingAs($admin)
        ->delete("/programs/{$program->id}")
        ->assertRedirect('/programs');

    expect($event->fresh()->primary_program_id)->toBeNull();
});

it('forbids users from creating programs', function () {
    $user = User::factory()->withRole(RoleName::User)->create();
    $event = Event::factory()->create();

    $this->actingAs($user)
        ->post('/programs', [
            'name' => 'Test',
            'visibility' => 'public',
            'event_id' => $event->id,
        ])
        ->assertForbidden();
});

it('allows admins to assign sponsors to a program', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $program = Program::factory()->create();
    $sponsors = Sponsor::factory()->count(3)->create();

    $this->actingAs($admin)
        ->patch("/programs/{$program->id}", [
            'name' => $program->name,
            'visibility' => $program->visibility->value,
            'sponsor_ids' => [$sponsors[0]->id, $sponsors[2]->id],
        ])
        ->assertRedirect();

    expect($program->fresh()->sponsors->pluck('id')->sort()->values()->all())
        ->toBe([$sponsors[0]->id, $sponsors[2]->id]);
});

it('allows admins to update sponsors on a program', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $program = Program::factory()->create();
    $sponsors = Sponsor::factory()->count(3)->create();

    $program->sponsors()->sync([$sponsors[0]->id, $sponsors[1]->id]);

    $this->actingAs($admin)
        ->patch("/programs/{$program->id}", [
            'name' => $program->name,
            'visibility' => $program->visibility->value,
            'sponsor_ids' => [$sponsors[1]->id, $sponsors[2]->id],
        ])
        ->assertRedirect();

    expect($program->fresh()->sponsors->pluck('id')->sort()->values()->all())
        ->toBe([$sponsors[1]->id, $sponsors[2]->id]);
});

it('allows admins to assign sponsors to individual time slots', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $program = Program::factory()->create();
    $slot = TimeSlot::factory()->for($program)->create();
    $sponsors = Sponsor::factory()->count(2)->create();

    $this->actingAs($admin)
        ->patch("/programs/{$program->id}", [
            'name' => $program->name,
            'visibility' => $program->visibility->value,
            'time_slots' => [
                [
                    'id' => $slot->id,
                    'name' => $slot->name,
                    'starts_at' => $slot->starts_at->format('Y-m-d H:i:s'),
                    'visibility' => $slot->visibility->value,
                    'sponsor_ids' => [$sponsors[0]->id, $sponsors[1]->id],
                ],
            ],
        ])
        ->assertRedirect();

    expect($slot->fresh()->sponsors->pluck('id')->sort()->values()->all())
        ->toBe([$sponsors[0]->id, $sponsors[1]->id]);
});

it('allows admins to assign sponsors to both program and time slots', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $program = Program::factory()->create();
    $sponsors = Sponsor::factory()->count(3)->create();

    $this->actingAs($admin)
        ->patch("/programs/{$program->id}", [
            'name' => $program->name,
            'visibility' => $program->visibility->value,
            'sponsor_ids' => [$sponsors[0]->id],
            'time_slots' => [
                [
                    'name' => 'Opening',
                    'starts_at' => '2026-07-01 09:00:00',
                    'visibility' => 'public',
                    'sponsor_ids' => [$sponsors[1]->id, $sponsors[2]->id],
                ],
            ],
        ])
        ->assertRedirect();

    $program->refresh();
    expect($program->sponsors->pluck('id')->all())->toBe([$sponsors[0]->id]);

    $newSlot = $program->timeSlots->first();
    expect($newSlot->sponsors->pluck('id')->sort()->values()->all())
        ->toBe([$sponsors[1]->id, $sponsors[2]->id]);
});

it('passes sponsors to the edit page', function () {
    $admin = User::factory()->withRole(RoleName::Admin)->create();
    $program = Program::factory()->create();
    Sponsor::factory()->count(2)->create();

    $this->actingAs($admin)
        ->get("/programs/{$program->id}")
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('programs/Edit')
                ->has('sponsors', 2)
        );
});
