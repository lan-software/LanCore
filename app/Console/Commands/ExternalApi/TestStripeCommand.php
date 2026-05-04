<?php

namespace App\Console\Commands\ExternalApi;

use App\Domain\Orchestration\Services\ExternalApiTester;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

#[Signature('external-apis:test:stripe')]
#[Description('Probe Stripe connectivity and return 0/1/2 for connected/failure/not configured.')]
class TestStripeCommand extends AbstractTestCommand
{
    /**
     * @return array{status: string, account?: string, error?: string}
     */
    protected function probe(ExternalApiTester $tester): array
    {
        return $tester->stripe();
    }

    protected function label(): string
    {
        return 'Stripe';
    }
}
