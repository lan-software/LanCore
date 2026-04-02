<?php

namespace App\Domain\Integration\Actions;

use App\Domain\Integration\Models\IntegrationApp;
use Illuminate\Support\Facades\DB;

/**
 * @see docs/mil-std-498/SRS.md INT-F-001
 */
class DeleteIntegrationApp
{
    public function execute(IntegrationApp $app): void
    {
        DB::transaction(function () use ($app): void {
            $app->webhooks()->delete();
            $app->tokens()->delete();
            $app->delete();
        });
    }
}
