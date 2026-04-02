<?php

namespace App\Domain\Integration\Actions;

use App\Domain\Integration\Models\IntegrationApp;
use App\Domain\Webhook\Enums\WebhookEvent;
use App\Domain\Webhook\Models\Webhook;

/**
 * @see docs/mil-std-498/SSS.md CAP-INT-004
 * @see docs/mil-std-498/SRS.md INT-F-001
 */
class SyncIntegrationWebhooks
{
    public function execute(IntegrationApp $app, ?string $announcementWebhookSecret = null, ?string $rolesWebhookSecret = null): void
    {
        $this->syncWebhook(
            $app,
            WebhookEvent::AnnouncementPublished,
            (bool) $app->send_announcements,
            $app->announcement_endpoint,
            $announcementWebhookSecret,
        );

        $this->syncWebhook(
            $app,
            WebhookEvent::UserRolesUpdated,
            (bool) $app->send_role_updates,
            $app->roles_endpoint,
            $rolesWebhookSecret,
        );
    }

    private function syncWebhook(IntegrationApp $app, WebhookEvent $event, bool $enabled, ?string $endpoint, ?string $secret): void
    {
        $existing = Webhook::query()
            ->where('integration_app_id', $app->id)
            ->where('event', $event->value)
            ->first();

        if ($enabled && $endpoint) {
            if ($existing) {
                $existing->update([
                    'name' => "Integration: {$app->name} — {$event->label()}",
                    'url' => $endpoint,
                    'secret' => $secret ?: null,
                    'is_active' => $app->is_active,
                ]);
            } else {
                Webhook::create([
                    'integration_app_id' => $app->id,
                    'name' => "Integration: {$app->name} — {$event->label()}",
                    'url' => $endpoint,
                    'event' => $event->value,
                    'secret' => $secret ?: null,
                    'is_active' => $app->is_active,
                    'description' => "Managed by {$app->name} integration. Do not edit manually.",
                ]);
            }
        } elseif ($existing) {
            $existing->delete();
        }
    }
}
