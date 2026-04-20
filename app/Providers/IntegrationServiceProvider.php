<?php

namespace App\Providers;

use App\Domain\Integration\Services\LancoreIntegrationsReconciler;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Throwable;

/**
 * Optional boot-time integration reconciliation.
 *
 * When `LANCORE_INTEGRATIONS_RECONCILE_ON_BOOT=true` (Docker Compose path),
 * the reconciler runs once per host process. A cache-backed lock prevents
 * Octane workers on the same host from racing — only the first worker
 * acquires the lock and runs; subsequent workers observe `alreadyOwned` and
 * skip silently.
 *
 * Kubernetes deployments (the canonical path) leave this env false and rely
 * on the Helm pre-install/pre-upgrade `integrations-sync` Job instead.
 *
 * @see docs/mil-std-498/SRS.md INT-F-012
 * @see docs/mil-std-498/SSDD.md §5.4.5
 */
class IntegrationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! config('integrations.reconcile_on_boot')) {
            return;
        }

        // Don't run during CLI contexts — the artisan command is the canonical
        // entry point. Running here would trigger the reconciler on every
        // `artisan` invocation including the one that just invoked it.
        if (app()->runningInConsole() && ! $this->isServingHttp()) {
            return;
        }

        $lock = Cache::lock('integrations:sync:boot', 60);

        if (! $lock->get()) {
            return;
        }

        try {
            /** @var LancoreIntegrationsReconciler $reconciler */
            $reconciler = $this->app->make(LancoreIntegrationsReconciler::class);
            $reconciler->reconcile();
        } catch (Throwable $e) {
            Log::error('[integrations:sync] boot-time reconciliation failed', [
                'error' => $e->getMessage(),
            ]);
        } finally {
            $lock->release();
        }
    }

    /**
     * True when we're serving HTTP (Octane or PHP-FPM), false for one-shot
     * artisan commands.
     */
    private function isServingHttp(): bool
    {
        /** @var ConsoleKernel|null $kernel */
        $kernel = $this->app->bound(ConsoleKernel::class) ? $this->app->make(ConsoleKernel::class) : null;

        return $kernel === null;
    }
}
