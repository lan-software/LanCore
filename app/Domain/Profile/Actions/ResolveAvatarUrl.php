<?php

namespace App\Domain\Profile\Actions;

use App\Domain\Profile\Enums\AvatarSource;
use App\Models\User;
use App\Support\StorageRole;
use Illuminate\Support\Facades\URL;

/**
 * Resolve the public-facing avatar URL for a user.
 *
 * Source priority:
 *   custom upload → Gravatar → built-in default.
 * The Steam source falls back to default until the Steam-linking
 * iteration ships.
 *
 * @see docs/mil-std-498/SRS.md USR-F-024, ICLIB-F-002
 * @see docs/mil-std-498/SSS.md CAP-USR-013
 */
class ResolveAvatarUrl
{
    public function execute(User $user): string
    {
        $source = $user->avatar_source instanceof AvatarSource
            ? $user->avatar_source
            : AvatarSource::tryFrom((string) $user->avatar_source) ?? AvatarSource::Default;

        return match ($source) {
            AvatarSource::Custom => $user->avatar_path !== null
                ? StorageRole::publicUrl($user->avatar_path)
                : $this->defaultUrl(),
            AvatarSource::Gravatar => $this->gravatarUrl($user),
            AvatarSource::Steam, AvatarSource::Default => $this->defaultUrl(),
        };
    }

    private function gravatarUrl(User $user): string
    {
        $hash = md5(strtolower(trim($user->email)));

        return sprintf('https://www.gravatar.com/avatar/%s?s=512&d=identicon', $hash);
    }

    private function defaultUrl(): string
    {
        return URL::asset('img/default-avatar.svg');
    }
}
