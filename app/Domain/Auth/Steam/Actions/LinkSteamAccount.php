<?php

namespace App\Domain\Auth\Steam\Actions;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Attaches a Steam identity to the currently authenticated user. Refuses
 * if the Steam ID is already linked to a different LanCore user.
 */
class LinkSteamAccount
{
    public function execute(User $user, SocialiteUser $steamUser): User
    {
        $steamId64 = (string) $steamUser->getId();

        if ($steamId64 === '') {
            throw ValidationException::withMessages([
                'steam' => __('auth.steam.errors.steamApiUnreachable'),
            ]);
        }

        $existing = User::query()
            ->where('steam_id_64', $steamId64)
            ->where('id', '!=', $user->id)
            ->first();

        if ($existing !== null) {
            throw ValidationException::withMessages([
                'steam' => __('auth.steam.errors.alreadyLinked'),
            ]);
        }

        $user->forceFill([
            'steam_id_64' => $steamId64,
            'steam_linked_at' => now(),
        ])->save();

        return $user;
    }
}
