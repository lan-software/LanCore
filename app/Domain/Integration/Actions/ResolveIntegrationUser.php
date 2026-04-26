<?php

namespace App\Domain\Integration\Actions;

use App\Domain\Integration\Models\IntegrationApp;
use App\Domain\Profile\Enums\AvatarSource;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md INT-F-005, ICLIB-F-002 (amended), ICLIB-F-010
 * @see docs/mil-std-498/SSDD.md §5.7.6 LanCoreUser DTO Public-Facing Identity Fields
 * @see docs/mil-std-498/IRS.md IF-SSO-006
 */
class ResolveIntegrationUser
{
    /**
     * Resolve a user and return a normalized, scope-filtered payload.
     *
     * @return array<string, mixed>|null
     */
    public function execute(User $user, IntegrationApp $app): ?array
    {
        $scopes = $app->allowed_scopes ?? [];

        if (! in_array('user:read', $scopes, true)) {
            return null;
        }

        $avatarSource = $user->avatar_source instanceof AvatarSource
            ? $user->avatar_source
            : (AvatarSource::tryFrom((string) $user->avatar_source) ?? AvatarSource::Default);

        $data = [
            'id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
        ];

        if (in_array('user:email', $scopes, true)) {
            $data['email'] = $user->email;
        }

        if (in_array('user:roles', $scopes, true)) {
            $user->loadMissing('roles');
            $data['roles'] = $user->roles->pluck('name')->values()->all();
        }

        $data['locale'] = $user->locale ?? config('app.fallback_locale');
        $data['avatar_url'] = $user->avatarUrl();
        $data['avatar_source'] = $avatarSource->value;
        $data['profile_url'] = $user->profileUrl();
        $data['created_at'] = $user->created_at?->toIso8601String();

        return $data;
    }
}
