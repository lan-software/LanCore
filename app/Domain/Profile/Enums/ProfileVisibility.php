<?php

namespace App\Domain\Profile\Enums;

use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md USR-F-023, USR-F-025
 * @see docs/mil-std-498/SSS.md CAP-USR-012, CAP-USR-014, SEC-021
 */
enum ProfileVisibility: string
{
    case Public = 'public';
    case LoggedIn = 'logged_in';
    case Private = 'private';

    /**
     * Whether a viewer (possibly null for unauthenticated requests) is
     * permitted to see the public profile of $owner under this visibility.
     */
    public function isVisibleTo(?User $viewer, User $owner): bool
    {
        return match ($this) {
            self::Public => true,
            self::LoggedIn => $viewer !== null,
            self::Private => $viewer !== null && $viewer->getKey() === $owner->getKey(),
        };
    }
}
