<?php

namespace App\Console\Commands\ExternalApi;

use App\Domain\Orchestration\Services\ExternalApiTester;
use Illuminate\Console\Command;

/**
 * Shared scaffolding for `external-apis:test:*` commands. Subclasses pick
 * the probe method on {@see ExternalApiTester}; this base handles printing
 * and exit code mapping so the contract is identical across providers.
 *
 * Exit codes:
 *  - 0 → connected
 *  - 1 → auth_failed / unreachable
 *  - 2 → not_configured
 *
 * @see docs/mil-std-498/SRS.md EXT-F-001..005
 */
abstract class AbstractTestCommand extends Command
{
    public function handle(ExternalApiTester $tester): int
    {
        $result = $this->probe($tester);
        $label = $this->label();
        $status = $result['status'];

        return match ($status) {
            ExternalApiTester::STATUS_CONNECTED => $this->reportConnected($label, $result),
            ExternalApiTester::STATUS_NOT_CONFIGURED => $this->reportNotConfigured($label, $result),
            default => $this->reportFailure($label, $result),
        };
    }

    /**
     * @return array{status: string, account?: string, error?: string}
     */
    abstract protected function probe(ExternalApiTester $tester): array;

    abstract protected function label(): string;

    /**
     * @param  array{status: string, account?: string, error?: string}  $result
     */
    private function reportConnected(string $label, array $result): int
    {
        $account = $result['account'] ?? 'OK';
        $this->info("[{$label}] connected ({$account}).");

        return self::SUCCESS;
    }

    /**
     * @param  array{status: string, account?: string, error?: string}  $result
     */
    private function reportNotConfigured(string $label, array $result): int
    {
        $error = $result['error'] ?? 'Configuration is missing.';
        $this->warn("[{$label}] not configured: {$error}");

        return 2;
    }

    /**
     * @param  array{status: string, account?: string, error?: string}  $result
     */
    private function reportFailure(string $label, array $result): int
    {
        $error = $result['error'] ?? 'Unknown failure.';
        $this->error("[{$label}] {$result['status']}: {$error}");

        return self::FAILURE;
    }
}
