<?php

use App\Models\User;

/**
 * @see docs/mil-std-498/STD.md §4.25 Profile Customization Tests (preview)
 * @see docs/mil-std-498/SRS.md USR-F-026
 */
test('preview renders even when visibility is private', function () {
    $user = User::factory()->create([
        'username' => 'neo_42',
        'profile_visibility' => 'private',
    ]);

    $this->actingAs($user)->get(route('profile.preview'))->assertOk();
});

test('preview is 404 for users without a username', function () {
    $user = User::factory()->withoutUsername()->create();

    $this->actingAs($user)->get(route('profile.preview'))->assertNotFound();
});
