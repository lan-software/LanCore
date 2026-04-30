<?php

namespace App\Domain\DataLifecycle\Support;

use App\Models\User;

/**
 * Produces stable, non-PII display names for anonymized users.
 * The suffix is derived from the user's email_hash so the same anonymized
 * user always renders identically across the UI.
 */
final class AnonymizedNameGenerator
{
    public function displayName(User $user): string
    {
        return 'Deleted User #'.$this->suffix($user);
    }

    public function username(User $user): string
    {
        return 'deleted_'.$user->getKey();
    }

    public function placeholderEmail(User $user): string
    {
        return sprintf('deleted-%d@anonymized.invalid', $user->getKey());
    }

    private function suffix(User $user): string
    {
        $hash = $user->email_hash ?? hash('sha256', (string) $user->getKey());

        return strtoupper(substr($hash, 0, 6));
    }
}
