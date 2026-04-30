<?php

use App\Domain\DataLifecycle\Actions\RequestUserDeletion;
use App\Domain\DataLifecycle\Enums\DeletionInitiator;
use App\Models\User;
use Database\Seeders\RetentionPolicySeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(RetentionPolicySeeder::class);
    Mail::fake();
});

it('returns 423 Locked on mutating requests when the user is in grace', function () {
    $user = User::factory()->create();

    app(RequestUserDeletion::class)->execute(
        subject: $user,
        initiator: DeletionInitiator::User,
    );

    $this->actingAs($user->refresh())
        ->post('/cookie-preferences', ['preferences' => []])
        ->assertStatus(423);
});

it('GET requests still pass for the locked user', function () {
    $user = User::factory()->create();

    app(RequestUserDeletion::class)->execute(
        subject: $user,
        initiator: DeletionInitiator::User,
    );

    $this->actingAs($user->refresh())
        ->get('/account/delete')
        ->assertSuccessful();
});
