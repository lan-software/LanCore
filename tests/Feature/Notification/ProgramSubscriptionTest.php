<?php

use App\Domain\Notification\Models\ProgramNotificationSubscription;
use App\Domain\Program\Models\Program;
use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    Role::updateOrCreate(['name' => RoleName::User->value], ['label' => 'User']);
});

it('allows users to subscribe to program notifications', function () {
    $user = User::factory()->create();
    $program = Program::factory()->create();

    $this->actingAs($user)
        ->post("/programs/{$program->id}/subscribe")
        ->assertSuccessful()
        ->assertJson(['subscribed' => true]);

    expect(ProgramNotificationSubscription::where('user_id', $user->id)->where('program_id', $program->id)->exists())->toBeTrue();
});

it('allows users to unsubscribe from program notifications', function () {
    $user = User::factory()->create();
    $program = Program::factory()->create();

    ProgramNotificationSubscription::create([
        'user_id' => $user->id,
        'program_id' => $program->id,
    ]);

    $this->actingAs($user)
        ->post("/programs/{$program->id}/subscribe")
        ->assertSuccessful()
        ->assertJson(['subscribed' => false]);

    expect(ProgramNotificationSubscription::where('user_id', $user->id)->where('program_id', $program->id)->exists())->toBeFalse();
});

it('toggles subscription state on repeated calls', function () {
    $user = User::factory()->create();
    $program = Program::factory()->create();

    $this->actingAs($user)
        ->post("/programs/{$program->id}/subscribe")
        ->assertJson(['subscribed' => true]);

    $this->actingAs($user)
        ->post("/programs/{$program->id}/subscribe")
        ->assertJson(['subscribed' => false]);

    $this->actingAs($user)
        ->post("/programs/{$program->id}/subscribe")
        ->assertJson(['subscribed' => true]);
});

it('requires authentication to subscribe to a program', function () {
    $program = Program::factory()->create();

    $this->post("/programs/{$program->id}/subscribe")
        ->assertRedirect('/login');
});
