<?php

namespace App\Domain\Integration\Actions;

use App\Domain\Integration\Models\IntegrationApp;
use App\Domain\Webhook\Enums\WebhookEvent;
use App\Domain\Webhook\Models\Webhook;

class SyncIntegrationWebhooks
{
    public function execute(IntegrationApp $app): void
    {
        $this->syncWebhook(
            $app,
            WebhookEvent::AnnouncementPublished,
            (bool) $app->send_announcements,
            $app->announcement_endpoint,
        );
    }

    private function syncWebhook(IntegrationApp $app, WebhookEvent $event, bool $enabled, ?string $endpoint): void
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
                    'is_active' => $app->is_active,
                ]);
            } else {
                Webhook::create([
                    'integration_app_id' => $app->id,
                    'name' => "Integration: {$app->name} — {$event->label()}",
                    'url' => $endpoint,
                    'event' => $event->value,
                    'is_active' => $app->is_active,
                    'description' => "Managed by {$app->name} integration. Do not edit manually.",
                ]);
            }
        } elseif ($existing) {
            $existing->delete();
        }
    }
}
