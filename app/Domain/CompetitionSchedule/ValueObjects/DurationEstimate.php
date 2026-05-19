<?php

namespace App\Domain\CompetitionSchedule\ValueObjects;

/**
 * @see docs/mil-std-498/SRS.md COMP-SCH-003
 */
final readonly class DurationEstimate
{
    public function __construct(
        public int $minutes,
        public string $hash,
        public string $strategy,
    ) {}
}
