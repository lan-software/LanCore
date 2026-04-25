<?php

namespace App\Domain\Integration\Services;

use App\Domain\Integration\Actions\SetIntegrationTokenPlaintext;
use App\Domain\Integration\Actions\SyncIntegrationWebhooks;
use App\Domain\Integration\Actions\UpsertIntegrationApp;
use App\Domain\Integration\Exceptions\IntegrationConfigurationException;
use App\Domain\Integration\Models\IntegrationApp;
use App\Domain\Webhook\Models\Webhook;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Reconciles the declarative `config/integrations.php` configuration into
 * the database. Each slug listed in `integrations.apps` becomes a config-
 * managed IntegrationApp: its row is upserted, its tokens are deleted and
 * replaced with the config-seeded token (when provided), and its webhook
 * subscriptions are deleted and recreated with the config-supplied secrets.
 *
 * Slugs NOT listed in the configuration are left untouched — those remain
 * UI/Artisan-managed.
 *
 * Token material and webhook secrets come from the caller (typically the
 * Helm umbrella chart's shared seed Secret). This class does not generate
 * either.
 *
 * @see docs/mil-std-498/SRS.md INT-F-011, INT-F-012, INT-F-013, INT-F-014
 * @see docs/mil-std-498/SSDD.md §5.4.5
 * @see docs/mil-std-498/IRS.md  §3.5a IF-INTCFG
 */
class LancoreIntegrationsReconciler
{
    public const TOKEN_NAME = 'config-seeded';

    public function __construct(
        private readonly ConfigRepository $config,
        private readonly UpsertIntegrationApp $upsertApp,
        private readonly SetIntegrationTokenPlaintext $setToken,
        private readonly SyncIntegrationWebhooks $syncWebhooks,
    ) {}

    /**
     * Reconcile every configured app (or a filtered subset) against the DB.
     *
     * @param  list<string>  $onlySlugs  If non-empty, limit to these slugs.
     * @param  bool  $strict  When true, throw IntegrationConfigurationException on
     *                        any slug whose token is null/empty. The artisan
     *                        command opts in; boot-time reconciliation does not,
     *                        so misconfigured envs do not bootloop Octane workers.
     * @return list<array{slug: string, created: bool, token_rotated: bool, webhooks_refreshed: int}>
     */
    public function reconcile(array $onlySlugs = [], bool $dryRun = false, bool $strict = false): array
    {
        /** @var array<string, array<string, mixed>>|null $apps */
        $apps = $this->config->get('integrations.apps');

        if (! is_array($apps)) {
            throw new RuntimeException('integrations.apps is not an array — is config/integrations.php present?');
        }

        $releaseContext = $this->releaseContext();
        $summary = [];

        foreach ($apps as $slug => $definition) {
            if ($onlySlugs !== [] && ! in_array($slug, $onlySlugs, true)) {
                continue;
            }

            $resolved = $this->resolveDefinition($slug, $definition);

            if ($strict && empty($resolved['token'])) {
                throw IntegrationConfigurationException::missingToken($slug);
            }

            $outcome = $dryRun
                ? $this->describe($slug, $resolved)
                : $this->applyOne($slug, $resolved);

            $summary[] = $outcome;

            Log::info('[integrations:sync] reconciled slug', [
                'slug' => $slug,
                'created' => $outcome['created'],
                'token_rotated' => $outcome['token_rotated'],
                'webhooks_refreshed' => $outcome['webhooks_refreshed'],
                'dry_run' => $dryRun,
                'release' => $releaseContext,
            ]);
        }

        return $summary;
    }

    /**
     * Expand the config-supplied definition into the full set of attributes
     * the DB rows need. Computes host / callback_url / nav_url defaults
     * from the top-level `domain` + `satellite_host_style` + `scheme`.
     *
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    public function resolveDefinition(string $slug, array $definition): array
    {
        $domain = $this->config->get('integrations.domain');
        $scheme = $this->config->get('integrations.scheme', 'https');
        $style = $this->config->get('integrations.satellite_host_style', 'flat');

        $host = $definition['host'] ?? null;
        if ($host === null || $host === '') {
            $host = match ($style) {
                'flat' => $domain ? "{$slug}.{$domain}" : null,
                'prefixed' => $domain ? "{$slug}.lancore.{$domain}" : null,
                'custom' => throw new RuntimeException("satellite_host_style=custom but integrations.apps.{$slug}.host is unset"),
                default => throw new RuntimeException("Unknown satellite_host_style: {$style}"),
            };
        }

        if ($host === null || $host === '') {
            throw new RuntimeException("Cannot resolve host for slug '{$slug}' — set integrations.domain or integrations.apps.{$slug}.host");
        }

        $callbackPath = $definition['callback_path'] ?? '/auth/callback';
        $callbackUrl = "{$scheme}://{$host}{$callbackPath}";
        $navUrl = $definition['nav_url'] ?? "{$scheme}://{$host}";

        $announcementPath = $definition['announcement_path'] ?? '/api/webhooks/announcements';
        $rolesPath = $definition['roles_path'] ?? '/api/webhooks/roles';

        return [
            'name' => $definition['name'] ?? ucfirst($slug),
            'description' => $definition['description'] ?? null,
            'host' => $host,
            'callback_url' => $callbackUrl,
            'allowed_scopes' => $definition['scopes'] ?? ['user:read', 'user:email', 'user:roles'],
            'is_active' => $definition['is_active'] ?? true,
            'nav_url' => $navUrl,
            'nav_icon' => $definition['nav_icon'] ?? null,
            'nav_label' => $definition['nav_label'] ?? ($definition['name'] ?? ucfirst($slug)),
            'send_announcements' => $definition['send_announcements'] ?? false,
            'announcement_endpoint' => "{$scheme}://{$host}{$announcementPath}",
            'send_role_updates' => $definition['send_role_updates'] ?? false,
            'roles_endpoint' => "{$scheme}://{$host}{$rolesPath}",
            'token' => $definition['token'] ?? null,
            'announcement_webhook_secret' => $definition['announcement_webhook_secret'] ?? null,
            'roles_webhook_secret' => $definition['roles_webhook_secret'] ?? null,
        ];
    }

    /**
     * Does the given slug appear in `config/integrations.php`?
     */
    public function isConfigManaged(string $slug): bool
    {
        /** @var array<string, mixed>|null $apps */
        $apps = $this->config->get('integrations.apps');

        return is_array($apps) && array_key_exists($slug, $apps);
    }

    /**
     * @param  array<string, mixed>  $resolved
     * @return array{slug: string, created: bool, token_rotated: bool, webhooks_refreshed: int}
     */
    private function applyOne(string $slug, array $resolved): array
    {
        return DB::transaction(function () use ($slug, $resolved): array {
            $existing = IntegrationApp::where('slug', $slug)->first();
            $created = $existing === null;

            $app = $this->upsertApp->execute($slug, [
                'name' => $resolved['name'],
                'description' => $resolved['description'],
                'callback_url' => $resolved['callback_url'],
                'allowed_scopes' => $resolved['allowed_scopes'],
                'is_active' => $resolved['is_active'],
                'nav_url' => $resolved['nav_url'],
                'nav_icon' => $resolved['nav_icon'],
                'nav_label' => $resolved['nav_label'],
                'send_announcements' => $resolved['send_announcements'],
                'announcement_endpoint' => $resolved['announcement_endpoint'],
                'send_role_updates' => $resolved['send_role_updates'],
                'roles_endpoint' => $resolved['roles_endpoint'],
            ]);

            $tokenRotated = false;
            $app->tokens()->delete();
            if (! empty($resolved['token'])) {
                $this->setToken->execute($app, self::TOKEN_NAME, $resolved['token']);
                $tokenRotated = true;
            } else {
                Log::warning('[integrations:sync] no token provided for slug — satellite will not be able to authenticate', [
                    'slug' => $slug,
                ]);
            }

            Webhook::where('integration_app_id', $app->id)->delete();
            $this->syncWebhooks->execute(
                $app,
                $resolved['announcement_webhook_secret'],
                $resolved['roles_webhook_secret'],
            );
            $webhooksRefreshed = Webhook::where('integration_app_id', $app->id)->count();

            return [
                'slug' => $slug,
                'created' => $created,
                'token_rotated' => $tokenRotated,
                'webhooks_refreshed' => $webhooksRefreshed,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $resolved
     * @return array{slug: string, created: bool, token_rotated: bool, webhooks_refreshed: int}
     */
    private function describe(string $slug, array $resolved): array
    {
        $existing = IntegrationApp::where('slug', $slug)->first();

        $webhooks = 0;
        if ($resolved['send_announcements'] ?? false) {
            $webhooks++;
        }
        if ($resolved['send_role_updates'] ?? false) {
            $webhooks++;
        }

        return [
            'slug' => $slug,
            'created' => $existing === null,
            'token_rotated' => ! empty($resolved['token']),
            'webhooks_refreshed' => $webhooks,
        ];
    }

    /**
     * @return array{name: ?string, revision: ?string}
     */
    private function releaseContext(): array
    {
        return [
            'name' => $this->config->get('integrations.release.name'),
            'revision' => $this->config->get('integrations.release.revision'),
        ];
    }
}
