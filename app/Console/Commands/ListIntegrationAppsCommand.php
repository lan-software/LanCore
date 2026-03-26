<?php

namespace App\Console\Commands;

use App\Domain\Integration\Models\IntegrationApp;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('integration:list')]
#[Description('List all integration applications')]
class ListIntegrationAppsCommand extends Command
{
    public function handle(): int
    {
        $apps = IntegrationApp::withCount('tokens', 'activeTokens')->orderBy('name')->get();

        if ($apps->isEmpty()) {
            $this->info('No integration apps found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Slug', 'Active', 'Scopes', 'Tokens (Active/Total)'],
            $apps->map(fn (IntegrationApp $app) => [
                $app->id,
                $app->name,
                $app->slug,
                $app->is_active ? '✓' : '✗',
                implode(', ', $app->allowed_scopes ?? []),
                "{$app->active_tokens_count}/{$app->tokens_count}",
            ]),
        );

        return self::SUCCESS;
    }
}
