<?php

use App\Models\User;

/**
 * Privacy carve-out: real name, email, phone, address, country, locale
 * must NOT appear in any public-profile response, regardless of viewer
 * authentication state. Driven by USR-F-012 + USR-F-023 + SEC-021.
 *
 * @see docs/mil-std-498/STD.md §4.24 Public Profile Tests (leakage)
 */
test('public profile does not leak real name, email, phone, or address', function () {
    $user = User::factory()->create([
        'name' => 'Markus Kohn',
        'username' => 'neo_42',
        'email' => 'markus@example.com',
        'phone' => '+49-555-1234567',
        'street' => 'Mainstrasse 12',
        'city' => 'Leipzig',
        'zip_code' => '04109',
        'country' => 'DE',
        'locale' => 'de',
        'profile_visibility' => 'public',
    ]);

    $body = $this->get('/u/neo_42')->assertOk()->getContent();

    expect($body)
        ->not->toContain('Markus Kohn')
        ->not->toContain('Markus')
        ->not->toContain('Kohn')
        ->not->toContain('markus@example.com')
        ->not->toContain('+49-555-1234567')
        ->not->toContain('Mainstrasse')
        ->not->toContain('Leipzig')
        ->not->toContain('04109');
});
