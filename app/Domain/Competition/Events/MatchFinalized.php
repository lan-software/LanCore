<?php

namespace App\Domain\Competition\Events;

use App\Domain\Competition\Enums\MatchFinalizationSource;
use App\Domain\Competition\Models\Competition;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched once when a match transitions to a finalized state — i.e. its
 * result is persisted as final by either the participant-confirm path or an
 * admin force-submission. Downstream consumers (Chat write-lock, future
 * notification listeners) use this to react without re-deriving "is this
 * match done?" from existing signals.
 *
 * @see docs/mil-std-498/SRS.md COM-F-MATCH-FINAL-001..003
 */
class MatchFinalized
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Competition $competition,
        public readonly string $matchId,
        public readonly MatchFinalizationSource $source,
    ) {}
}
