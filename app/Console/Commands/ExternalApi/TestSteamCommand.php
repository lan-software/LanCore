<?php

namespace App\Console\Commands\ExternalApi;

use App\Domain\Orchestration\Services\ExternalApiTester;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

#[Signature('external-apis:test:steam')]
#[Description('Probe Steam Web API connectivity and return 0/1/2 for connected/failure/not configured.')]
class TestSteamCommand extends AbstractTestCommand
{
    /**
     * @return array{status: string, account?: string, error?: string}
     */
    protected function probe(ExternalApiTester $tester): array
    {
        return $tester->steam();
    }

    protected function label(): string
    {
        return 'Steam';
    }
}
