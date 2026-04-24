<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Throwable;

/**
 * Register a PayPal webhook subscription with the PayPal API and persist the
 * resulting webhook id back into the local `.env` so that `verifyWebHookLocally`
 * has the id it needs. Idempotent: a subsequent run updates the existing
 * subscription via `updateWebHook`.
 *
 * @see docs/mil-std-498/SRS.md SHP-F-020
 * @see docs/mil-std-498/IRS.md IF-PAYPAL-003
 */
#[Signature('paypal:webhook:register {url? : The absolute HTTPS URL PayPal should POST to. Falls back to the webhooks.paypal named route on APP_URL.}')]
#[Description('Register or update the PayPal webhook subscription and write PAYPAL_WEBHOOK_ID to .env.')]
class PayPalWebhookRegister extends Command
{
    /**
     * Event types we listen for. Keep in sync with PayPalWebhookController.
     *
     * @var array<int, string>
     */
    protected const EVENTS = [
        'PAYMENT.CAPTURE.COMPLETED',
        'PAYMENT.CAPTURE.DENIED',
        'PAYMENT.CAPTURE.REVERSED',
        'CHECKOUT.ORDER.APPROVED',
    ];

    public function handle(): int
    {
        $url = (string) ($this->argument('url') ?: rtrim((string) config('app.url'), '/').'/webhooks/paypal');

        if (! str_starts_with($url, 'https://')) {
            $this->error("PayPal only accepts HTTPS webhook URLs. Got: {$url}");

            return self::FAILURE;
        }

        try {
            $client = new PayPalClient;
            $client->setApiCredentials(config('paypal'));
            $client->getAccessToken();
        } catch (Throwable $e) {
            $this->error('Failed to authenticate with PayPal: '.$e->getMessage());

            return self::FAILURE;
        }

        $existingId = $this->findExistingWebhookId($client, $url);

        try {
            if ($existingId) {
                $this->info("Updating existing PayPal webhook (id={$existingId}) event list.");
                $client->updateWebHook($existingId, [[
                    'op' => 'replace',
                    'path' => '/event_types',
                    'value' => array_map(fn (string $name): array => ['name' => $name], self::EVENTS),
                ]]);
                $webhookId = $existingId;
            } else {
                $this->info("Creating new PayPal webhook subscription for {$url}.");
                $response = $client->createWebHook($url, self::EVENTS);

                if (! is_array($response) || empty($response['id'])) {
                    $this->error('PayPal createWebHook did not return an id: '.json_encode($response));

                    return self::FAILURE;
                }

                $webhookId = (string) $response['id'];
            }
        } catch (Throwable $e) {
            $this->error('PayPal API call failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Webhook id: {$webhookId}");
        $this->writeEnv($webhookId);

        return self::SUCCESS;
    }

    /**
     * Look for an existing webhook subscription at the same URL. PayPal
     * returns a 400 when trying to create a duplicate, so we update instead.
     */
    private function findExistingWebhookId(PayPalClient $client, string $url): ?string
    {
        try {
            $response = $client->listWebHooks();
        } catch (Throwable $e) {
            $this->warn('Could not list existing webhooks: '.$e->getMessage());

            return null;
        }

        if (! is_array($response)) {
            return null;
        }

        foreach ((array) ($response['webhooks'] ?? []) as $webhook) {
            if (is_array($webhook) && ($webhook['url'] ?? null) === $url && ! empty($webhook['id'])) {
                return (string) $webhook['id'];
            }
        }

        return null;
    }

    private function writeEnv(string $webhookId): void
    {
        $envPath = base_path('.env');

        if (! is_file($envPath) || ! is_writable($envPath)) {
            $this->warn("Could not write to {$envPath}. Please set PAYPAL_WEBHOOK_ID={$webhookId} manually.");

            return;
        }

        $contents = (string) file_get_contents($envPath);

        if (preg_match('/^PAYPAL_WEBHOOK_ID=.*/m', $contents)) {
            $contents = preg_replace('/^PAYPAL_WEBHOOK_ID=.*/m', "PAYPAL_WEBHOOK_ID={$webhookId}", $contents);
        } else {
            $contents = rtrim($contents)."\nPAYPAL_WEBHOOK_ID={$webhookId}\n";
        }

        file_put_contents($envPath, $contents);
        $this->info("Wrote PAYPAL_WEBHOOK_ID to {$envPath}. Restart Octane / queue workers to pick up the new value.");
    }
}
