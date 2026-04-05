<?php

namespace App\Domain\Integration\Actions;

use App\Domain\Integration\Models\IntegrationApp;
use App\Models\User;

/**
 * @see docs/mil-std-498/SRS.md INT-F-005
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

        $data = [
            'id' => $user->id,
            'username' => $user->name,
        ];

        if (in_array('user:email', $scopes, true)) {
            $data['email'] = $user->email;
        }

        if (in_array('user:roles', $scopes, true)) {
            $user->loadMissing('roles');
            $data['roles'] = $user->roles->pluck('name')->values()->all();
        }

        $data['locale'] = app()->getLocale();
        $data['avatar_url'] = null;
        $data['created_at'] = $user->created_at?->toIso8601String();

        return $data;
    }
}
