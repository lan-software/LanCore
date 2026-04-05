<?php

namespace App\Domain\Orchestration\Listeners;

use App\Domain\Competition\Events\MatchReadyForOrchestration;
use App\Domain\Orchestration\Jobs\ProcessMatchOrchestration;
use App\Domain\Orchestration\Models\OrchestrationJob;
use Illuminate\Support\Facades\Log;

class HandleMatchReadyForOrchestration
{
    public function handle(MatchReadyForOrchestration $event): void
    {
        $competition = $event->competition;

        $job = OrchestrationJob::create([
            'competition_id' => $competition->id,
            'lanbrackets_match_id' => $event->lanbracketsMatchId,
            'game_id' => $competition->game_id,
            'game_mode_id' => $competition->game_mode_id,
            'match_config' => $event->matchData,
        ]);

        Log::info("Orchestration job {$job->id} created for match {$event->lanbracketsMatchId}.");

        ProcessMatchOrchestration::dispatch($job);
    }
}
