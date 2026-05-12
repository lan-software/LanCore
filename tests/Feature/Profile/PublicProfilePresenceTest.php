<?php

use App\Domain\Presence\Enums\PresenceStatus;
use App\Domain\Presence\Services\PresenceTracker;
use App\Models\User;

it('passes the resolved presence status as a prop on a public profile', function (): void {
    $owner = User::factory()->create([
        'username' => 'public_owner',
        'profile_visibility' => 'public',
    ]);

    $tracker = Mockery::mock(PresenceTracker::class);
    $tracker->shouldReceive('statusFor')
        ->with(Mockery::on(fn (User $u) => $u->id === $owner->id))
        ->andReturn(PresenceStatus::Active);
    $tracker->shouldReceive('statusFor')->andReturn(PresenceStatus::Offline);
    $tracker->shouldReceive('touch')->andReturnNull();
    $tracker->shouldReceive('bulkStatusFor')->andReturn([]);
    $this->app->instance(PresenceTracker::class, $tracker);

    $this->get('/u/public_owner')
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('u/Show')
                ->where('presence', 'active')
                ->etc(),
        );
});

it('does not leak presence on a private profile (returns 404)', function (): void {
    $owner = User::factory()->create([
        'username' => 'private_owner',
        'profile_visibility' => 'private',
    ]);

    $tracker = Mockery::mock(PresenceTracker::class);
    $tracker->shouldReceive('statusFor')
        ->with(Mockery::on(fn (User $u) => $u->id === $owner->id))
        ->never();
    $tracker->shouldReceive('statusFor')->andReturn(PresenceStatus::Offline);
    $tracker->shouldReceive('touch')->andReturnNull();
    $tracker->shouldReceive('bulkStatusFor')->andReturn([]);
    $this->app->instance(PresenceTracker::class, $tracker);

    $this->get('/u/private_owner')->assertNotFound();
});

it('does not leak presence on a logged_in profile viewed anonymously', function (): void {
    $owner = User::factory()->create([
        'username' => 'gated_owner',
        'profile_visibility' => 'logged_in',
    ]);

    $tracker = Mockery::mock(PresenceTracker::class);
    $tracker->shouldReceive('statusFor')
        ->with(Mockery::on(fn (User $u) => $u->id === $owner->id))
        ->never();
    $tracker->shouldReceive('statusFor')->andReturn(PresenceStatus::Offline);
    $tracker->shouldReceive('touch')->andReturnNull();
    $tracker->shouldReceive('bulkStatusFor')->andReturn([]);
    $this->app->instance(PresenceTracker::class, $tracker);

    $this->get('/u/gated_owner')->assertNotFound();
});
