<?php

namespace App\Console\Commands;

use App\Domain\Integration\Actions\RevokeIntegrationToken;
use App\Domain\Integration\Models\IntegrationApp;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('integration:revoke-token {app : The slug or ID of the integration app} {token : The ID of the token to revoke}')]
#[Description('Revoke an API token for an integration application')]
class RevokeIntegrationTokenCommand extends Command
{
    public function handle(RevokeIntegrationToken $revokeIntegrationToken): int
    {
        $identifier = $this->argument('app');

        $app = is_numeric($identifier)
            ? IntegrationApp::find((int) $identifier)
            : IntegrationApp::where('slug', $identifier)->first();

        if (! $app) {
            $this->error("Integration app '{$identifier}' not found.");

            return self::FAILURE;
        }

        $token = $app->tokens()->find((int) $this->argument('token'));

        if (! $token) {
            $this->error("Token '{$this->argument('token')}' not found for app '{$app->name}'.");

            return self::FAILURE;
        }

        if ($token->isRevoked()) {
            $this->warn('This token is already revoked.');

            return self::SUCCESS;
        }

        $revokeIntegrationToken->execute($token);

        $this->info("Token '{$token->name}' (ID: {$token->id}) has been revoked.");

        return self::SUCCESS;
    }
}
