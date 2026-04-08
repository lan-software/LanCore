<?php

use App\Models\User;
use Illuminate\Support\Facades\Config;

it('blocks profile information updates in demo mode', function (): void {
    Config::set('app.demo', true);
    $user = User::factory()->create();

    $status = $this->actingAs($user)
        ->put('/user/profile-information', ['name' => 'New Name', 'email' => 'new@example.com'])
        ->status();

    expect($status)->toBeIn([403, 404, 405]);
});

it('blocks password updates in demo mode', function (): void {
    Config::set('app.demo', true);
    $user = User::factory()->create();

    $status = $this->actingAs($user)
        ->put('/user/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->status();

    expect($status)->toBeIn([403, 404, 405]);
});

it('blocks account deletion in demo mode', function (): void {
    Config::set('app.demo', true);
    $user = User::factory()->create();

    $status = $this->actingAs($user)->delete('/user')->status();

    expect($status)->toBeIn([403, 404, 405]);
});

it('blocks registration POST in demo mode', function (): void {
    Config::set('app.demo', true);

    $this->post('/register', [
        'name' => 'Test',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertForbidden();
});

it('allows registration when demo mode is off', function (): void {
    Config::set('app.demo', false);

    $status = $this->post('/register', [
        'name' => 'Test',
        'email' => 'new-user@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->status();

    expect($status)->not->toBe(403);
});
