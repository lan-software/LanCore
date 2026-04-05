<?php

namespace App\Domain\Orchestration\Actions;

use App\Domain\Orchestration\Enums\OrchestrationJobStatus;
use App\Domain\Orchestration\Jobs\ProcessMatchOrchestration;
use App\Domain\Orchestration\Models\OrchestrationJob;
use Illuminate\Validation\ValidationException;

/**
 * Resets a failed orchestration job to pending and re-dispatches it.
 */
class RetryOrchestrationJob
{
    public function execute(OrchestrationJob $job): void
    {
        if ($job->status !== OrchestrationJobStatus::Failed) {
            throw ValidationException::withMessages([
                'status' => 'Only failed jobs can be retried.',
            ]);
        }

        $job->update([
            'status' => OrchestrationJobStatus::Pending,
            'error_message' => null,
        ]);

        ProcessMatchOrchestration::dispatch($job);
    }
}
