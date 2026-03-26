<?php

namespace App\Domain\Integration\Actions;

use App\Domain\Integration\Models\IntegrationApp;
use Illuminate\Support\Facades\DB;

class UpdateIntegrationApp
{
    /**
     * @param  array{name?: string, description?: string|null, callback_url?: string|null, allowed_scopes?: array<string>|null, is_active?: bool}  $attributes
     */
    public function execute(IntegrationApp $app, array $attributes): void
    {
        DB::transaction(function () use ($app, $attributes): void {
            $app->fill($attributes)->save();
        });
    }
}
