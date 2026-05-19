<?php

namespace App\Domain\Competition\Events;

use App\Domain\Competition\Models\Competition;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchReadyForOrchestration
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<string, mixed>  $matchData
     */
    public function __construct(
        public readonly Competition $competition,
        public readonly string $matchId,
        public readonly array $matchData,
    ) {}
}
