<?php

namespace App\Domain\Orchestration\Actions;

use App\Domain\Orchestration\Enums\OrchestrationJobStatus;
use App\Domain\Orchestration\Models\OrchestrationJob;
use Illuminate\Validation\ValidationException;

/**
 * Cancels a pending or failed orchestration job.
 */
class CancelOrchestrationJob
{
    public function execute(OrchestrationJob $job): void
    {
        if (! in_array($job->status, [OrchestrationJobStatus::Pending, OrchestrationJobStatus::Failed])) {
            throw ValidationException::withMessages([
                'status' => 'Only pending or failed jobs can be cancelled.',
            ]);
        }

        $job->update(['status' => OrchestrationJobStatus::Cancelled]);
    }
}
