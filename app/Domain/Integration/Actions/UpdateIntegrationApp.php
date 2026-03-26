<?php

namespace App\Domain\Integration\Actions;

use App\Domain\Integration\Models\IntegrationApp;
use Illuminate\Support\Facades\DB;

class UpdateIntegrationApp
{
    public function __construct(private readonly SyncIntegrationWebhooks $syncWebhooks) {}

    /**
     * @param  array{name?: string, description?: string|null, callback_url?: string|null, allowed_scopes?: array<string>|null, is_active?: bool, send_announcements?: bool, announcement_endpoint?: string|null}  $attributes
     */
    public function execute(IntegrationApp $app, array $attributes): void
    {
        DB::transaction(function () use ($app, $attributes): void {
            $app->fill($attributes)->save();

            $this->syncWebhooks->execute($app);
        });
    }
}
