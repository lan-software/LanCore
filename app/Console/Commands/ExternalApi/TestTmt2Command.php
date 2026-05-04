<?php

namespace App\Console\Commands\ExternalApi;

use App\Domain\Orchestration\Services\ExternalApiTester;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

#[Signature('external-apis:test:tmt2')]
#[Description('Probe TMT2 connectivity and return 0/1/2 for connected/failure/not configured.')]
class TestTmt2Command extends AbstractTestCommand
{
    /**
     * @return array{status: string, account?: string, error?: string}
     */
    protected function probe(ExternalApiTester $tester): array
    {
        return $tester->tmt2();
    }

    protected function label(): string
    {
        return 'TMT2';
    }
}
