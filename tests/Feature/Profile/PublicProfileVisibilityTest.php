<?php

use App\Models\User;

/**
 * @see docs/mil-std-498/STD.md §4.24 Public Profile Tests
 * @see docs/mil-std-498/SRS.md USR-F-023
 * @see docs/mil-std-498/SSS.md SEC-021
 */
test('public visibility renders for anonymous viewer', function () {
    User::factory()->create([
        'username' => 'neo_42',
        'profile_visibility' => 'public',
    ]);

    $this->get('/u/neo_42')->assertOk();
});

test('logged_in visibility is 404 for anonymous viewer', function () {
    User::factory()->create([
        'username' => 'neo_42',
        'profile_visibility' => 'logged_in',
    ]);

    $this->get('/u/neo_42')->assertNotFound();
});

test('logged_in visibility renders for authenticated viewer', function () {
    User::factory()->create([
        'username' => 'neo_42',
        'profile_visibility' => 'logged_in',
    ]);

    $viewer = User::factory()->create();

    $this->actingAs($viewer)->get('/u/neo_42')->assertOk();
});

test('private visibility is 404 for non-owner', function () {
    User::factory()->create([
        'username' => 'neo_42',
        'profile_visibility' => 'private',
    ]);

    $viewer = User::factory()->create();

    $this->actingAs($viewer)->get('/u/neo_42')->assertNotFound();
});

test('private visibility renders for owner', function () {
    $owner = User::factory()->create([
        'username' => 'neo_42',
        'profile_visibility' => 'private',
    ]);

    $this->actingAs($owner)->get('/u/neo_42')->assertOk();
});

test('username is resolved case-insensitively', function () {
    User::factory()->create([
        'username' => 'Neo_42',
        'profile_visibility' => 'public',
    ]);

    $this->get('/u/NEO_42')->assertOk();
});

test('non-existent username and forbidden profile both return 404', function () {
    User::factory()->create([
        'username' => 'private_user',
        'profile_visibility' => 'private',
    ]);

    $this->get('/u/does_not_exist')->assertNotFound();
    $this->get('/u/private_user')->assertNotFound();
});
