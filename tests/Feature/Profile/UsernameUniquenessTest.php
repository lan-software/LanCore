<?php

use App\Models\User;

/**
 * @see docs/mil-std-498/STD.md §4.23 Username Tests (uniqueness)
 * @see docs/mil-std-498/SRS.md USR-F-022
 */
test('username uniqueness is case-insensitive', function () {
    User::factory()->create(['username' => 'Neo']);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'username' => 'NEO',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertSessionHasErrors('username');
});

test('user can change their own username without unique conflict', function () {
    $user = User::factory()->create(['username' => 'phantom']);

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'username' => 'phantom',
            'email' => $user->email,
        ]);

    $response->assertSessionHasNoErrors();
});
