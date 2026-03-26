<?php

namespace App\Domain\Integration\Actions;

use App\Domain\Integration\Models\IntegrationApp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateIntegrationApp
{
    public function __construct(private readonly SyncIntegrationWebhooks $syncWebhooks) {}

    /**
     * @param  array{name: string, slug?: string, description?: string|null, callback_url?: string|null, allowed_scopes?: array<string>|null, is_active?: bool, send_announcements?: bool, announcement_endpoint?: string|null}  $attributes
     */
    public function execute(array $attributes): IntegrationApp
    {
        return DB::transaction(function () use ($attributes): IntegrationApp {
            if (! isset($attributes['slug'])) {
                $attributes['slug'] = Str::slug($attributes['name']);
            }

            $app = IntegrationApp::create($attributes);

            $this->syncWebhooks->execute($app);

            return $app;
        });
    }
}
