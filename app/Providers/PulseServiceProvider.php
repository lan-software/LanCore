<?php

namespace App\Providers;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class PulseServiceProvider extends ServiceProvider
{
    /**
     * Override Pulse's default `viewPulse` gate (local-only) so the dashboard
     * is reachable in non-local environments by Superadmins. Mirrors how
     * Horizon access is restricted in {@see HorizonServiceProvider}.
     */
    public function boot(): void
    {
        Gate::define(
            'viewPulse',
            fn (?User $user = null): bool => $user !== null && $user->hasRole(RoleName::Superadmin),
        );
    }
}
