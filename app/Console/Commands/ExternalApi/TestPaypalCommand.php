<?php

namespace App\Console\Commands\ExternalApi;

use App\Domain\Orchestration\Services\ExternalApiTester;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

#[Signature('external-apis:test:paypal')]
#[Description('Probe PayPal connectivity and return 0/1/2 for connected/failure/not configured.')]
class TestPaypalCommand extends AbstractTestCommand
{
    /**
     * @return array{status: string, account?: string, error?: string}
     */
    protected function probe(ExternalApiTester $tester): array
    {
        return $tester->paypal();
    }

    protected function label(): string
    {
        return 'PayPal';
    }
}
