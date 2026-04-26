<?php

use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md USR-F-024
 */
test('profile_emoji accepts a single emoji', function (): void {
    $user = User::factory()->create(['username' => 'gamer1']);

    $this->actingAs($user)
        ->patch('/settings/profile', [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'profile_emoji' => '🎮',
        ])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->profile_emoji)->toBe('🎮');
});

test('profile_emoji accepts a ZWJ-joined compound emoji', function (): void {
    $user = User::factory()->create(['username' => 'gamer2']);

    $this->actingAs($user)
        ->patch('/settings/profile', [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'profile_emoji' => '👨‍💻',
        ])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->profile_emoji)->toBe('👨‍💻');
});

test('profile_emoji rejects plain text', function (): void {
    $user = User::factory()->create(['username' => 'gamer3']);

    $this->actingAs($user)
        ->patch('/settings/profile', [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'profile_emoji' => 'lol',
        ])
        ->assertSessionHasErrors('profile_emoji');
});

test('profile_emoji rejects mixed text and emoji', function (): void {
    $user = User::factory()->create(['username' => 'gamer4']);

    $this->actingAs($user)
        ->patch('/settings/profile', [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'profile_emoji' => '🎮gg',
        ])
        ->assertSessionHasErrors('profile_emoji');
});

test('profile_emoji allows clearing via empty string', function (): void {
    $user = User::factory()->create([
        'username' => 'gamer5',
        'profile_emoji' => '🎮',
    ]);

    $this->actingAs($user)
        ->patch('/settings/profile', [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'profile_emoji' => '',
        ])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->profile_emoji)->toBeNull();
});
