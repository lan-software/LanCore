<?php

namespace App\Domain\Integration\Actions;

use App\Domain\Integration\Models\IntegrationApp;
use Illuminate\Support\Facades\DB;

class DeleteIntegrationApp
{
    public function execute(IntegrationApp $app): void
    {
        DB::transaction(function () use ($app): void {
            $app->tokens()->delete();
            $app->delete();
        });
    }
}
