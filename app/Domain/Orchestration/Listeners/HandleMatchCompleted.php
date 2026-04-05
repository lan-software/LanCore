<?php

namespace App\Domain\Orchestration\Listeners;

use App\Domain\Competition\Events\MatchCompleted;
use App\Domain\Orchestration\Actions\CompleteOrchestrationJob;
use App\Domain\Orchestration\Enums\OrchestrationJobStatus;
use App\Domain\Orchestration\Models\OrchestrationJob;

class HandleMatchCompleted
{
    public function __construct(
        private readonly CompleteOrchestrationJob $completeJob,
    ) {}

    public function handle(MatchCompleted $event): void
    {
        $job = OrchestrationJob::query()
            ->where('competition_id', $event->competition->id)
            ->where('lanbrackets_match_id', $event->lanbracketsMatchId)
            ->where('status', OrchestrationJobStatus::Active)
            ->first();

        if ($job !== null) {
            $this->completeJob->execute($job);
        }
    }
}
