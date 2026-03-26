<?php

namespace App\Console\Commands;

use App\Domain\Integration\Actions\CreateIntegrationToken;
use App\Domain\Integration\Models\IntegrationApp;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('integration:token {app : The slug or ID of the integration app} {name : A label for this token} {--expires= : Expiration date (e.g. "+30 days", "2026-12-31")}')]
#[Description('Generate an API token for an integration application')]
class CreateIntegrationTokenCommand extends Command
{
    public function handle(CreateIntegrationToken $createIntegrationToken): int
    {
        $identifier = $this->argument('app');

        $app = is_numeric($identifier)
            ? IntegrationApp::find((int) $identifier)
            : IntegrationApp::where('slug', $identifier)->first();

        if (! $app) {
            $this->error("Integration app '{$identifier}' not found.");

            return self::FAILURE;
        }

        if (! $app->is_active) {
            $this->warn("Warning: Integration app '{$app->name}' is currently inactive.");
        }

        $expiresAt = null;
        if ($expires = $this->option('expires')) {
            $expiresAt = new \DateTimeImmutable($expires);
        }

        $result = $createIntegrationToken->execute($app, $this->argument('name'), $expiresAt);

        $this->newLine();
        $this->info('Token created successfully. Copy it now — it will not be shown again.');
        $this->newLine();
        $this->line("  <fg=green>{$result['plain_text']}</>");
        $this->newLine();

        $this->table(['App', 'Token Name', 'Prefix', 'Expires'], [
            [$app->name, $result['token']->name, $result['token']->plain_text_prefix.'…', $result['token']->expires_at?->toDateTimeString() ?? 'Never'],
        ]);

        return self::SUCCESS;
    }
}
