<?php

namespace App\Domain\Auth\Steam\Actions;

use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Removes the Steam linkage from a user. Refuses when the user has no
 * usable password — those accounts would be locked out without an
 * alternative way to sign in.
 */
class UnlinkSteamAccount
{
    public function execute(User $user): User
    {
        if (! $user->hasUsablePassword()) {
            throw ValidationException::withMessages([
                'steam' => __('auth.steam.errors.unlinkRequiresPassword'),
            ]);
        }

        $user->forceFill([
            'steam_id_64' => null,
            'steam_linked_at' => null,
        ])->save();

        return $user;
    }
}
