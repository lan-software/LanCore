<?php

namespace App\Console\Commands;

use App\Domain\Integration\Actions\CreateIntegrationApp;
use App\Domain\Integration\Actions\CreateIntegrationToken;
use App\Domain\Integration\Models\IntegrationApp;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SetupDevIntegrationCommand extends Command
{
    protected $signature = 'integration:setup-dev {app : The satellite app to configure (lanbrackets, lanshout, lanhelp, lanentrance)}';

    protected $description = 'Create an integration app with token for local development and output the .env snippet for the satellite app';

    /** @var array<string, array{name: string, port: int, scopes: list<string>, roles_webhook: bool}> */
    private const APPS = [
        'lanbrackets' => [
            'name' => 'LanBrackets',
            'port' => 81,
            'scopes' => ['user:read', 'user:email', 'user:roles'],
            'roles_webhook' => true,
        ],
        'lanshout' => [
            'name' => 'LanShout',
            'port' => 82,
            'scopes' => ['user:read', 'user:email', 'user:roles'],
            'roles_webhook' => true,
        ],
        'lanhelp' => [
            'name' => 'LanHelp',
            'port' => 83,
            'scopes' => ['user:read', 'user:email', 'user:roles'],
            'roles_webhook' => true,
        ],
        'lanentrance' => [
            'name' => 'LanEntrance',
            'port' => 84,
            'scopes' => ['user:read', 'user:email', 'user:roles'],
            'roles_webhook' => true,
        ],
    ];

    public function handle(CreateIntegrationApp $createApp, CreateIntegrationToken $createToken): int
    {
        $slug = strtolower($this->argument('app'));

        if (! isset(self::APPS[$slug])) {
            $this->error("Unknown app '{$slug}'. Available: ".implode(', ', array_keys(self::APPS)));

            return self::FAILURE;
        }

        $config = self::APPS[$slug];
        $baseUrl = "http://localhost:{$config['port']}";
        $appInternalUrl = "http://{$slug}.test";

        $existing = IntegrationApp::where('slug', $slug)->first();

        if ($existing) {
            $this->warn("Integration app '{$slug}' already exists (ID: {$existing->id}).");

            if (! $this->confirm('Generate a new token for the existing app?', true)) {
                return self::SUCCESS;
            }

            $app = $existing;
        } else {
            $rolesWebhookSecret = $config['roles_webhook'] ? Str::random(64) : null;

            $attributes = [
                'name' => $config['name'],
                'slug' => $slug,
                'description' => "{$config['name']} local development integration",
                'callback_url' => "{$baseUrl}/auth/callback",
                'allowed_scopes' => $config['scopes'],
                'is_active' => true,
                'send_role_updates' => $config['roles_webhook'],
                'roles_endpoint' => $config['roles_webhook'] ? "{$appInternalUrl}/api/webhooks/roles" : null,
                'roles_webhook_secret' => $rolesWebhookSecret,
                'nav_url' => $baseUrl,
                'nav_label' => $config['name'],
            ];

            $app = $createApp->execute($attributes);
            $this->components->info("Integration app '{$app->name}' created.");
        }

        $tokenResult = $createToken->execute($app, 'dev');
        $plainToken = $tokenResult['plain_text'];

        $rolesSecret = $this->resolveRolesWebhookSecret($app);

        $this->newLine();
        $this->components->info("Add these to your {$config['name']}/.env file:");
        $this->newLine();

        $envLines = [
            "LANCORE_ENABLED=true",
            "LANCORE_BASE_URL=http://localhost",
            "LANCORE_INTERNAL_URL=http://lancore.test",
            "LANCORE_TOKEN={$plainToken}",
            "LANCORE_APP_SLUG={$slug}",
            "LANCORE_CALLBACK_URL={$baseUrl}/auth/callback",
        ];

        if ($rolesSecret) {
            $envLines[] = "LANCORE_WEBHOOK_SECRET={$rolesSecret}";
        }

        foreach ($envLines as $line) {
            $this->line("  <fg=green>{$line}</>");
        }

        $this->newLine();

        return self::SUCCESS;
    }

    private function resolveRolesWebhookSecret(IntegrationApp $app): ?string
    {
        $webhook = $app->webhooks()
            ->where('event', 'user.roles_updated')
            ->first();

        return $webhook?->secret;
    }
}
