<?php

namespace App\Domain\Seating\Support;

use Illuminate\Support\Collection;

/**
 * Value object returned by UpdateSeatPlan. Encodes the two-phase save contract:
 * first call with confirm=false reports invalidations (no DB write), second
 * call with confirm=true actually writes.
 *
 * @see docs/mil-std-498/SRS.md SET-F-012
 */
final class UpdateSeatPlanResult
{
    /**
     * @param  Collection<int, array<string, mixed>>  $invalidations
     */
    private function __construct(
        private readonly bool $needsConfirmation,
        public readonly Collection $invalidations,
        public readonly int $releasedCount,
    ) {}

    /**
     * @param  Collection<int, array<string, mixed>>  $invalidations
     */
    public static function pending(Collection $invalidations): self
    {
        return new self(true, $invalidations, 0);
    }

    public static function saved(int $releasedCount): self
    {
        /** @var Collection<int, array<string, mixed>> $empty */
        $empty = collect();

        return new self(false, $empty, $releasedCount);
    }

    public function needsConfirmation(): bool
    {
        return $this->needsConfirmation;
    }
}
