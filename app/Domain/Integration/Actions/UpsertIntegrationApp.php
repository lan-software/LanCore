<?php

namespace App\Domain\Integration\Actions;

use App\Domain\Integration\Models\IntegrationApp;
use Illuminate\Support\Facades\DB;

/**
 * Upsert an IntegrationApp row by slug, applying every config-driven field.
 * Unlike CreateIntegrationApp this does NOT synchronise webhooks — the
 * reconciler does that separately so it can wipe-and-recreate rather than
 * merge.
 *
 * @see docs/mil-std-498/SRS.md INT-F-011, INT-F-012
 * @see docs/mil-std-498/SSDD.md §5.4.5
 */
class UpsertIntegrationApp
{
    /**
     * @param  array{name: string, description?: string|null, callback_url?: string|null, allowed_scopes?: array<string>|null, is_active?: bool, nav_url?: string|null, nav_icon?: string|null, nav_label?: string|null, send_announcements?: bool, announcement_endpoint?: string|null, send_role_updates?: bool, roles_endpoint?: string|null}  $attributes
     */
    public function execute(string $slug, array $attributes): IntegrationApp
    {
        return DB::transaction(function () use ($slug, $attributes): IntegrationApp {
            $attributes['is_active'] = $attributes['is_active'] ?? true;

            return IntegrationApp::updateOrCreate(['slug' => $slug], $attributes);
        });
    }
}
