<?php

namespace App\Domain\Competition\Events;

use App\Domain\Competition\Models\Competition;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchCompleted
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<string, mixed>  $matchData
     */
    public function __construct(
        public readonly Competition $competition,
        public readonly int $lanbracketsMatchId,
        public readonly array $matchData,
    ) {}
}
