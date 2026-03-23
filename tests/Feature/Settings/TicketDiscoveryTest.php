<?php

use App\Models\User;

test('ticket discovery settings page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('ticket-discovery.edit'))
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('settings/TicketDiscovery')
                ->has('isTicketDiscoverable')
                ->has('ticketDiscoveryAllowlist')
        );
});

test('unauthenticated users cannot access ticket discovery settings', function () {
    $this->get(route('ticket-discovery.edit'))
        ->assertRedirect(route('login'));
});

test('user can enable global discoverability', function () {
    $user = User::factory()->create([
        'is_ticket_discoverable' => false,
        'ticket_discovery_allowlist' => ['alice', 'bob'],
    ]);

    $this->actingAs($user)
        ->patch(route('ticket-discovery.update'), [
            'is_ticket_discoverable' => true,
            'ticket_discovery_allowlist' => [],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $user->refresh();

    expect($user->is_ticket_discoverable)->toBeTrue();
    expect($user->ticket_discovery_allowlist)->toBe([]);
});

test('user can restrict discoverability to allowlist', function () {
    $user = User::factory()->create([
        'is_ticket_discoverable' => true,
    ]);

    $this->actingAs($user)
        ->patch(route('ticket-discovery.update'), [
            'is_ticket_discoverable' => false,
            'ticket_discovery_allowlist' => ['alice', 'bob'],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $user->refresh();

    expect($user->is_ticket_discoverable)->toBeFalse();
    expect($user->ticket_discovery_allowlist)->toBe(['alice', 'bob']);
});

test('allowlist is cleared when enabling global discoverability', function () {
    $user = User::factory()->create([
        'is_ticket_discoverable' => false,
        'ticket_discovery_allowlist' => ['alice'],
    ]);

    $this->actingAs($user)
        ->patch(route('ticket-discovery.update'), [
            'is_ticket_discoverable' => true,
            'ticket_discovery_allowlist' => ['alice'],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $user->refresh();

    expect($user->ticket_discovery_allowlist)->toBe([]);
});

test('duplicate usernames in allowlist are deduplicated', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('ticket-discovery.update'), [
            'is_ticket_discoverable' => false,
            'ticket_discovery_allowlist' => ['alice', 'alice', 'bob'],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $user->refresh();

    expect($user->ticket_discovery_allowlist)->toBe(['alice', 'bob']);
});

test('is_ticket_discoverable validation requires boolean', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('ticket-discovery.update'), [
            'is_ticket_discoverable' => 'not-a-boolean',
            'ticket_discovery_allowlist' => [],
        ])
        ->assertSessionHasErrors('is_ticket_discoverable');
});

test('saved allowlist is visible on page reload', function () {
    $user = User::factory()->create([
        'is_ticket_discoverable' => true,
        'ticket_discovery_allowlist' => null,
    ]);

    // Submit the form to restrict to allowlist
    $this->actingAs($user)
        ->patch(route('ticket-discovery.update'), [
            'is_ticket_discoverable' => false,
            'ticket_discovery_allowlist' => ['alice', 'bob'],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    // Reload the page and check the Inertia props
    $this->actingAs($user)
        ->get(route('ticket-discovery.edit'))
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('settings/TicketDiscovery')
                ->where('isTicketDiscoverable', false)
                ->where('ticketDiscoveryAllowlist', ['alice', 'bob'])
        );
});

test('empty allowlist is stored when globally discoverable', function () {
    $user = User::factory()->create([
        'is_ticket_discoverable' => false,
        'ticket_discovery_allowlist' => ['alice'],
    ]);

    $this->actingAs($user)
        ->patch(route('ticket-discovery.update'), [
            'is_ticket_discoverable' => true,
            'ticket_discovery_allowlist' => [],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    // Reload the page
    $this->actingAs($user)
        ->get(route('ticket-discovery.edit'))
        ->assertSuccessful()
        ->assertInertia(
            fn ($page) => $page
                ->component('settings/TicketDiscovery')
                ->where('isTicketDiscoverable', true)
                ->where('ticketDiscoveryAllowlist', [])
        );
});
