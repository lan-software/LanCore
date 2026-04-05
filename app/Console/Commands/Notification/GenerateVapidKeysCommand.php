<?php

namespace App\Console\Commands\Notification;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Minishlink\WebPush\VAPID;
use Throwable;

class GenerateVapidKeysCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'webpush:vapid
                    {--show : Display the keys instead of modifying files}
                    {--force : Force the operation to run when in production}';

    protected $description = 'Generate VAPID keys for browser push notifications';

    public function handle(): int
    {
        try {
            $keys = VAPID::createVapidKeys();
        } catch (Throwable $e) {
            $this->error('Failed to generate VAPID keys: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($this->option('show')) {
            $this->line('VAPID_PUBLIC_KEY='.$keys['publicKey']);
            $this->line('VAPID_PRIVATE_KEY='.$keys['privateKey']);

            return self::SUCCESS;
        }

        if (! $this->setKeyInEnvironmentFile('VAPID_PUBLIC_KEY', $keys['publicKey'])
            || ! $this->setKeyInEnvironmentFile('VAPID_PRIVATE_KEY', $keys['privateKey'])) {
            return self::FAILURE;
        }

        $this->laravel['config']['webpush.vapid.public_key'] = $keys['publicKey'];
        $this->laravel['config']['webpush.vapid.private_key'] = $keys['privateKey'];

        $this->components->info('VAPID keys set successfully.');

        return self::SUCCESS;
    }

    private function setKeyInEnvironmentFile(string $envKey, string $value): bool
    {
        $envPath = $this->laravel->environmentFilePath();
        $contents = file_get_contents($envPath);

        $currentValue = match ($envKey) {
            'VAPID_PUBLIC_KEY' => config('webpush.vapid.public_key', ''),
            'VAPID_PRIVATE_KEY' => config('webpush.vapid.private_key', ''),
            default => '',
        };

        if ($currentValue !== '' && $currentValue !== null && ! $this->confirmToProceed()) {
            return false;
        }

        $escaped = preg_quote('='.$currentValue, '/');
        $replaced = preg_replace(
            "/^{$envKey}{$escaped}/m",
            "{$envKey}={$value}",
            $contents
        );

        if ($replaced === $contents || $replaced === null) {
            $this->components->error("Unable to set {$envKey}. No {$envKey} variable was found in the .env file.");

            return false;
        }

        file_put_contents($envPath, $replaced);

        return true;
    }
}
