<?php

use App\Models\User;

/**
 * @see docs/mil-std-498/STD.md §4.23 Username Tests
 * @see docs/mil-std-498/SRS.md USR-F-022
 */
test('registration requires a username', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertSessionHasErrors('username');
});

test('registration accepts a valid username', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'username' => 'neo_42',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertSessionHasNoErrors();
    expect(User::where('username', 'neo_42')->exists())->toBeTrue();
});

test('registration rejects username shorter than 3 characters', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'username' => 'ab',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertSessionHasErrors('username');
});

test('registration rejects username longer than 32 characters', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'username' => str_repeat('a', 33),
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertSessionHasErrors('username');
});

test('registration rejects disallowed characters', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'username' => 'with space',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertSessionHasErrors('username');
});

test('registration rejects leading or trailing punctuation', function () {
    foreach (['_neo', 'neo_', '-neo', 'neo-'] as $candidate) {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'username' => $candidate,
            'email' => "test+{$candidate}@example.com",
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors('username');
    }
});
