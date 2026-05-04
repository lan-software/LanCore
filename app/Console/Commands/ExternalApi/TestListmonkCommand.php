<?php

namespace App\Console\Commands\ExternalApi;

use App\Domain\Orchestration\Services\ExternalApiTester;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

#[Signature('external-apis:test:listmonk')]
#[Description('Probe Listmonk connectivity and return 0/1/2 for connected/failure/not configured.')]
class TestListmonkCommand extends AbstractTestCommand
{
    /**
     * @return array{status: string, account?: string, error?: string}
     */
    protected function probe(ExternalApiTester $tester): array
    {
        return $tester->listmonk();
    }

    protected function label(): string
    {
        return 'Listmonk';
    }
}
