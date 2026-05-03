<?php

namespace App\Domain\Auth\Steam\Enums;

use App\Models\User;

/**
 * Derived (non-stored) status describing how a user is linked to Steam.
 * Computed from `users.steam_id_64` and `users.password` presence.
 *
 * @see docs/mil-std-498/SRS.md USR-F-024
 */
enum SteamLinkStatus: string
{
    case Linked = 'linked';
    case SteamOnly = 'steam_only';
    case NotLinked = 'not_linked';

    public static function for(User $user): self
    {
        if ($user->steam_id_64 === null) {
            return self::NotLinked;
        }

        return $user->hasUsablePassword()
            ? self::Linked
            : self::SteamOnly;
    }

    public function label(): string
    {
        return match ($this) {
            self::Linked => 'Linked',
            self::SteamOnly => 'Steam-only',
            self::NotLinked => 'Not linked',
        };
    }
}
