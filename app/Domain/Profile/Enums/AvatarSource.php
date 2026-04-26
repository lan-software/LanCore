<?php

namespace App\Domain\Profile\Enums;

/**
 * @see docs/mil-std-498/SRS.md USR-F-024
 * @see docs/mil-std-498/SSS.md CAP-USR-013
 */
enum AvatarSource: string
{
    case Default = 'default';
    case Gravatar = 'gravatar';
    case Custom = 'custom';

    /**
     * Reserved for a future Steam account-linking iteration; currently
     * resolves to the default URL like AvatarSource::Default would.
     */
    case Steam = 'steam';

    public function isImplemented(): bool
    {
        return $this !== self::Steam;
    }
}
