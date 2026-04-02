<?php

/**
 * @see docs/mil-std-498/SSS.md CAP-PRG-004, CAP-NTF-001
 * @see docs/mil-std-498/SRS.md PRG-F-005, NTF-F-004
 */

use App\Domain\Notification\Models\ProgramNotificationSubscription;
use App\Domain\Program\Models\Program;
use App\Models\User;

it('creates a subscription record with correct user and program', function () {
    $user = User::factory()->create();
    $program = Program::factory()->create();

    $this->actingAs($user)
        ->post("/programs/{$program->id}/subscribe")
        ->assertSuccessful();

    $subscription = ProgramNotificationSubscription::where('user_id', $user->id)
        ->where('program_id', $program->id)
        ->first();

    expect($subscription)->not->toBeNull()
        ->and($subscription->user_id)->toBe($user->id)
        ->and($subscription->program_id)->toBe($program->id);
});

it('allows subscribing to multiple programs', function () {
    $user = User::factory()->create();
    $programA = Program::factory()->create();
    $programB = Program::factory()->create();

    $this->actingAs($user)
        ->post("/programs/{$programA->id}/subscribe")
        ->assertJson(['subscribed' => true]);

    $this->actingAs($user)
        ->post("/programs/{$programB->id}/subscribe")
        ->assertJson(['subscribed' => true]);

    expect(ProgramNotificationSubscription::where('user_id', $user->id)->count())->toBe(2);
});

it('does not create duplicate subscriptions for the same program', function () {
    $user = User::factory()->create();
    $program = Program::factory()->create();

    ProgramNotificationSubscription::create([
        'user_id' => $user->id,
        'program_id' => $program->id,
    ]);

    $this->actingAs($user)
        ->post("/programs/{$program->id}/subscribe")
        ->assertJson(['subscribed' => false]);

    expect(ProgramNotificationSubscription::where('user_id', $user->id)
        ->where('program_id', $program->id)
        ->count())->toBe(0);
});

it('isolates subscriptions between users', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $program = Program::factory()->create();

    $this->actingAs($userA)
        ->post("/programs/{$program->id}/subscribe")
        ->assertJson(['subscribed' => true]);

    $this->actingAs($userB)
        ->post("/programs/{$program->id}/subscribe")
        ->assertJson(['subscribed' => true]);

    expect(ProgramNotificationSubscription::where('program_id', $program->id)->count())->toBe(2);

    $this->actingAs($userA)
        ->post("/programs/{$program->id}/subscribe")
        ->assertJson(['subscribed' => false]);

    expect(ProgramNotificationSubscription::where('program_id', $program->id)->count())->toBe(1);
    expect(ProgramNotificationSubscription::where('user_id', $userB->id)->exists())->toBeTrue();
});

it('returns 404 for a non-existent program', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/programs/99999/subscribe')
        ->assertNotFound();
});

it('cleans up subscriptions when a program is deleted', function () {
    $user = User::factory()->create();
    $program = Program::factory()->create();

    ProgramNotificationSubscription::create([
        'user_id' => $user->id,
        'program_id' => $program->id,
    ]);

    $program->delete();

    expect(ProgramNotificationSubscription::where('user_id', $user->id)->count())->toBe(0);
});
