<?php

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;

it('writes the last activity key for authenticated demo requests', function (): void {
    Config::set('app.demo', true);
    Redis::shouldReceive('set')->once()->with('demo:last_activity', Mockery::type('string'));

    $user = User::factory()->create();
    $this->actingAs($user)->get('/');
});

it('does not write the activity key when demo mode is off', function (): void {
    Config::set('app.demo', false);
    Redis::shouldReceive('set')->never();

    $user = User::factory()->create();
    $this->actingAs($user)->get('/');
});

it('does not write the activity key for unauthenticated requests', function (): void {
    Config::set('app.demo', true);
    Redis::shouldReceive('set')->never();

    $this->get('/login');
});
