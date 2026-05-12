<?php

use App\Domain\Presence\Enums\PresenceStatus;
use App\Domain\Presence\Services\PresenceTracker;
use App\Models\User;

beforeEach(function (): void {
    $this->tracker = Mockery::mock(PresenceTracker::class);
    $this->app->instance(PresenceTracker::class, $this->tracker);
});

it('records a heartbeat for authenticated web requests', function (): void {
    $user = User::factory()->create();

    $this->tracker->shouldReceive('touch')
        ->once()
        ->with(Mockery::on(fn (User $u) => $u->is($user)));
    $this->tracker->shouldReceive('statusFor')
        ->andReturn(PresenceStatus::Active);

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

it('does not record a heartbeat for guest web requests', function (): void {
    $this->tracker->shouldReceive('touch')->never();
    $this->tracker->shouldReceive('statusFor')->never();

    $this->get(route('login'))->assertOk();
});

it('does not heartbeat on integration api routes', function (): void {
    $this->tracker->shouldReceive('touch')->never();
    $this->tracker->shouldReceive('statusFor')->never();

    $this->postJson('/api/integration/user/resolve', [])
        ->assertStatus(401);
});

it('exposes the presence shared prop as null for guests', function (): void {
    $this->tracker->shouldReceive('statusFor')->never();
    $this->tracker->shouldReceive('touch')->never();

    $this->get(route('login'))
        ->assertInertia(fn ($page) => $page->where('presence', null)->etc());
});

it('exposes the presence shared prop as the derived status for authenticated users', function (): void {
    $user = User::factory()->create();

    $this->tracker->shouldReceive('touch')->once();
    $this->tracker->shouldReceive('statusFor')
        ->andReturn(PresenceStatus::Active);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('presence.status', 'active')->etc());
});
