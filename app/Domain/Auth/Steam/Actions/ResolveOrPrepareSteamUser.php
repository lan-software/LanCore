<?php

namespace App\Domain\Auth\Steam\Actions;

use App\Domain\Auth\Steam\Data\PendingSteamRegistration;
use App\Models\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Returns the existing User for a Steam identity, or null when no match
 * is found. Callers are expected to stash a {@see PendingSteamRegistration}
 * in the session and redirect to the completion form when the result is null.
 */
class ResolveOrPrepareSteamUser
{
    public function execute(SocialiteUser $steamUser): ?User
    {
        $steamId64 = (string) $steamUser->getId();

        if ($steamId64 === '') {
            return null;
        }

        return User::query()->where('steam_id_64', $steamId64)->first();
    }
}
