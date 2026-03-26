<?php

namespace App\Console\Commands;

use App\Domain\Integration\Models\IntegrationApp;
use App\Domain\Integration\Models\IntegrationToken;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('integration:tokens {app : The slug or ID of the integration app}')]
#[Description('List all tokens for an integration application')]
class ListIntegrationTokensCommand extends Command
{
    public function handle(): int
    {
        $identifier = $this->argument('app');

        $app = is_numeric($identifier)
            ? IntegrationApp::find((int) $identifier)
            : IntegrationApp::where('slug', $identifier)->first();

        if (! $app) {
            $this->error("Integration app '{$identifier}' not found.");

            return self::FAILURE;
        }

        $tokens = $app->tokens()->orderByDesc('created_at')->get();

        if ($tokens->isEmpty()) {
            $this->info("No tokens found for '{$app->name}'.");

            return self::SUCCESS;
        }

        $this->info("Tokens for {$app->name} ({$app->slug}):");
        $this->newLine();

        $this->table(
            ['ID', 'Name', 'Prefix', 'Status', 'Last Used', 'Expires', 'Created'],
            $tokens->map(fn (IntegrationToken $token) => [
                $token->id,
                $token->name,
                $token->plain_text_prefix.'…',
                $this->tokenStatus($token),
                $token->last_used_at?->toDateTimeString() ?? 'Never',
                $token->expires_at?->toDateString() ?? 'Never',
                $token->created_at->toDateString(),
            ]),
        );

        return self::SUCCESS;
    }

    private function tokenStatus(IntegrationToken $token): string
    {
        if ($token->isRevoked()) {
            return '<fg=red>Revoked</>';
        }

        if ($token->isExpired()) {
            return '<fg=yellow>Expired</>';
        }

        return '<fg=green>Active</>';
    }
}
