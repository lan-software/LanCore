<?php

namespace App\Domain\Integration\Actions;

use App\Domain\Integration\Models\IntegrationApp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateIntegrationApp
{
    public function __construct(private readonly SyncIntegrationWebhooks $syncWebhooks) {}

    /**
     * @param  array{name: string, slug?: string, description?: string|null, callback_url?: string|null, allowed_scopes?: array<string>|null, is_active?: bool, send_announcements?: bool, announcement_endpoint?: string|null, announcement_webhook_secret?: string|null, send_role_updates?: bool, roles_endpoint?: string|null, roles_webhook_secret?: string|null}  $attributes
     */
    public function execute(array $attributes): IntegrationApp
    {
        $announcementWebhookSecret = $attributes['announcement_webhook_secret'] ?? null;
        $rolesWebhookSecret = $attributes['roles_webhook_secret'] ?? null;
        unset($attributes['announcement_webhook_secret'], $attributes['roles_webhook_secret']);

        return DB::transaction(function () use ($attributes, $announcementWebhookSecret, $rolesWebhookSecret): IntegrationApp {
            if (! isset($attributes['slug'])) {
                $attributes['slug'] = Str::slug($attributes['name']);
            }

            $app = IntegrationApp::create($attributes);

            $this->syncWebhooks->execute($app, $announcementWebhookSecret, $rolesWebhookSecret);

            return $app;
        });
    }
}
