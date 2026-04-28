<?php

use App\Domain\Auth\Steam\Data\PendingSteamRegistration;
use App\Domain\Auth\Steam\Http\Controllers\SteamAuthController;
use App\Models\User;
use Carbon\CarbonImmutable;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

function makeSteamSocialiteUser(string $steamId = '76561197960287930'): SocialiteUserContract
{
    $user = new SocialiteUser;
    $user->id = $steamId;
    $user->nickname = 'CoolGabe';
    $user->name = 'Gabe Newell';
    $user->avatar = 'https://avatars.akamai.steamstatic.com/avatar.jpg';
    $user->setRaw([
        'steamid' => $steamId,
        'personaname' => 'CoolGabe',
        'avatarfull' => 'https://avatars.akamai.steamstatic.com/avatar_full.jpg',
        'profileurl' => 'https://steamcommunity.com/id/coolgabe/',
        'loccountrycode' => 'US',
    ]);

    return $user;
}

function fakeSteamSocialite(string $steamId = '76561197960287930'): SocialiteUserContract
{
    $user = makeSteamSocialiteUser($steamId);

    Socialite::shouldReceive('driver')
        ->with('steam')
        ->andReturnSelf();
    Socialite::shouldReceive('user')->andReturn($user);
    Socialite::shouldReceive('redirect')->andReturn(redirect('https://steamcommunity.com/openid/login'));

    return $user;
}

test('steam redirect bounces the user to Steam', function () {
    fakeSteamSocialite();

    $response = $this->get(route('auth.steam.redirect'));

    $response->assertRedirect('https://steamcommunity.com/openid/login');
});

test('steam callback for a new identity stashes pending data and redirects to completion', function () {
    fakeSteamSocialite('76561197999999999');

    $response = $this->get(route('auth.steam.callback'));

    $response->assertRedirect(route('auth.steam.complete.show'));
    $this->assertGuest();

    $session = session(PendingSteamRegistration::SESSION_KEY);
    expect($session)->toBeArray()
        ->and($session['steam_id_64'])->toBe('76561197999999999')
        ->and($session['country_code'])->toBe('us');
});

test('steam callback for an existing user logs them in', function () {
    $user = User::factory()->create([
        'steam_id_64' => '76561197960287930',
        'steam_linked_at' => now(),
    ]);

    fakeSteamSocialite('76561197960287930');

    $response = $this->get(route('auth.steam.callback'));

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('completion form renders when a pending registration is present', function () {
    $pending = new PendingSteamRegistration(
        steamId64: '76561197960287930',
        personaName: 'CoolGabe',
        avatarUrl: 'https://example.test/avatar.jpg',
        profileUrl: 'https://steamcommunity.com/id/coolgabe/',
        countryCode: 'us',
        createdAt: CarbonImmutable::now(),
    );

    $this->withSession([PendingSteamRegistration::SESSION_KEY => $pending->toArray()])
        ->get(route('auth.steam.complete.show'))
        ->assertOk();
});

test('completion form redirects to login when no pending data is in session', function () {
    $this->get(route('auth.steam.complete.show'))
        ->assertRedirect(route('login'));
});

test('completion form redirects to login when pending data has expired', function () {
    $pending = new PendingSteamRegistration(
        steamId64: '76561197960287930',
        personaName: 'CoolGabe',
        avatarUrl: null,
        profileUrl: null,
        countryCode: null,
        createdAt: CarbonImmutable::now()->subHour(),
    );

    $this->withSession([PendingSteamRegistration::SESSION_KEY => $pending->toArray()])
        ->get(route('auth.steam.complete.show'))
        ->assertRedirect(route('login'));
});

test('completing the form creates a user without a password and logs them in', function () {
    $pending = new PendingSteamRegistration(
        steamId64: '76561197960287930',
        personaName: 'CoolGabe',
        avatarUrl: 'https://example.test/avatar.jpg',
        profileUrl: 'https://steamcommunity.com/id/coolgabe/',
        countryCode: 'us',
        createdAt: CarbonImmutable::now(),
    );

    $response = $this->withSession([PendingSteamRegistration::SESSION_KEY => $pending->toArray()])
        ->post(route('auth.steam.complete'), [
            'name' => 'Gabe Newell',
            'username' => 'coolgabe',
            'email' => 'gabe@example.com',
        ]);

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticated();

    $user = User::where('steam_id_64', '76561197960287930')->firstOrFail();
    expect($user->password)->toBeNull()
        ->and($user->email)->toBe('gabe@example.com')
        ->and($user->username)->toBe('coolgabe')
        ->and($user->country)->toBe('US')
        ->and($user->steam_linked_at)->not->toBeNull();
});

test('linking Steam from settings attaches the steam id to the current user', function () {
    $user = User::factory()->create();

    fakeSteamSocialite('76561197999999999');

    $this->actingAs($user)
        ->withSession([SteamAuthController::LINK_INTENT_KEY => true])
        ->get(route('auth.steam.callback'))
        ->assertRedirect(route('settings.linked-accounts.edit'));

    expect($user->fresh()->steam_id_64)->toBe('76561197999999999');
});

test('linking refuses when the steam account is already linked to a different user', function () {
    User::factory()->create(['steam_id_64' => '76561197960287930']);
    $other = User::factory()->create();

    fakeSteamSocialite('76561197960287930');

    $response = $this->actingAs($other)
        ->withSession([SteamAuthController::LINK_INTENT_KEY => true])
        ->get(route('auth.steam.callback'));

    $response->assertRedirect(route('settings.linked-accounts.edit'));
    $response->assertSessionHasErrors('steam');

    expect($other->fresh()->steam_id_64)->toBeNull();
});

test('unlinking Steam succeeds when the user has a usable password', function () {
    $user = User::factory()->create([
        'steam_id_64' => '76561197960287930',
        'steam_linked_at' => now(),
    ]);

    $response = $this->actingAs($user)->post(route('auth.steam.unlink'));

    $response->assertRedirect(route('settings.linked-accounts.edit'));
    expect($user->fresh()->steam_id_64)->toBeNull()
        ->and($user->fresh()->steam_linked_at)->toBeNull();
});

test('unlinking Steam is rejected when the user has no usable password', function () {
    $user = User::factory()->create([
        'password' => null,
        'steam_id_64' => '76561197960287930',
        'steam_linked_at' => now(),
    ]);

    $response = $this->actingAs($user)->post(route('auth.steam.unlink'));

    $response->assertRedirect(route('settings.linked-accounts.edit'));
    $response->assertSessionHasErrors('steam');
    expect($user->fresh()->steam_id_64)->toBe('76561197960287930');
});
