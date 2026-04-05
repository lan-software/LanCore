<?php

namespace App\Domain\Orchestration\Jobs;

use App\Domain\Orchestration\Actions\ProcessOrchestrationJob;
use App\Domain\Orchestration\Models\OrchestrationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * @see docs/mil-std-498/SRS.md ORC-F-007
 */
class ProcessMatchOrchestration implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(public readonly OrchestrationJob $orchestrationJob)
    {
        $this->onQueue('orchestration');
    }

    public function handle(ProcessOrchestrationJob $action): void
    {
        $action->execute($this->orchestrationJob);
    }
}
